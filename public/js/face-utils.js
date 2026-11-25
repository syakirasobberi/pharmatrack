/**
 * PharmaTrack face helpers (requires face-api.js to be loaded first).
 *
 * Two pipelines, tuned for ordinary laptops / kiosk PCs without a strong GPU:
 *  - FAST     (tiny detector + tiny landmarks): every frame, for the head-turn check and quality hints.
 *  - DESCRIBE (tiny detector + full landmarks + recognition net): only on the few frames used for
 *             identification / registration. Registration and kiosk use the same pipeline so their
 *             face fingerprints are directly comparable.
 *
 *  - FaceUtils.loadModels()                load everything both pipelines need
 *  - FaceUtils.detectFast() / describe()   run one frame through a pipeline
 *  - FaceUtils.checkQuality()              rejects unclear / small / side-on faces
 *  - FaceUtils.captureAverageDescriptor()  multi-frame registration: averages several good frames
 *  - FaceUtils.HeadTurnChallenge           liveness check: turn the head to a random side and back
 *
 * Add ?facedebug=1 to the page URL to see frame rate and head-turn values in the status text.
 */
(function (global) {
    'use strict';

    const MIN_SCORE = 0.5;          // tiny-detector confidence required for a usable frame
    const MIN_FACE_RATIO = 0.12;    // face box width must be >= 12% of the video width
    const MAX_YAW_RATIO = 0.3;      // nose offset from eye-centre, relative to eye distance (frontal check)

    const FAST_OPTIONS = () => new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 });
    const DESCRIBE_OPTIONS = () => new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.4 });

    const DEBUG = new URLSearchParams(location.search).has('facedebug');

    async function loadModels(uri = '/models') {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(uri),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(uri),
            faceapi.nets.faceLandmark68Net.loadFromUri(uri),
            faceapi.nets.faceRecognitionNet.loadFromUri(uri),
        ]);
    }

    /** Cheap per-frame detection: box + 68 landmarks from the tiny models (no descriptor). */
    function detectFast(input) {
        return faceapi.detectSingleFace(input, FAST_OPTIONS()).withFaceLandmarks(true);
    }

    /** Detection + full landmarks + 128-number face descriptor (slower; use on few frames only). */
    function describe(input) {
        return faceapi.detectSingleFace(input, DESCRIBE_OPTIONS()).withFaceLandmarks().withFaceDescriptor();
    }

    const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

    /** Eye Aspect Ratio (Soukupová & Čech, 2016): drops towards 0 when the eye closes. */
    function eyeAspectRatio(eye) {
        return (dist(eye[1], eye[5]) + dist(eye[2], eye[4])) / (2 * dist(eye[0], eye[3]));
    }

    function averageEar(landmarks) {
        return (eyeAspectRatio(landmarks.getLeftEye()) + eyeAspectRatio(landmarks.getRightEye())) / 2;
    }

    /**
     * Decide whether a detection (with landmarks) is good enough to use.
     * Returns { ok: boolean, reason: string } — reason is a user-facing hint.
     * allowTurn: skip the "face the camera directly" check (used during the head-turn challenge).
     */
    function checkQuality(result, videoWidth, { allowTurn = false } = {}) {
        if (!result) return { ok: false, reason: 'No face detected — please look at the camera.' };

        const { score, box } = result.detection;
        if (score < MIN_SCORE) return { ok: false, reason: 'Face unclear — improve lighting and hold still.' };
        if (videoWidth && box.width < videoWidth * MIN_FACE_RATIO) return { ok: false, reason: 'Please move closer to the camera.' };

        if (!allowTurn && Math.abs(yaw(result.landmarks)) > MAX_YAW_RATIO) {
            return { ok: false, reason: 'Please face the camera directly.' };
        }

        return { ok: true, reason: '' };
    }

    function averageDescriptors(list) {
        const out = new Array(128).fill(0);
        list.forEach(d => { for (let i = 0; i < 128; i++) out[i] += d[i]; });
        return out.map(v => v / list.length);
    }

    function euclidean(a, b) {
        let s = 0;
        for (let i = 0; i < a.length; i++) { const d = a[i] - b[i]; s += d * d; }
        return Math.sqrt(s);
    }

    /**
     * Multi-frame registration: collect `frames` good-quality descriptors and return their average.
     * Frames that disagree with the others (e.g. a different person stepped in) restart the capture.
     *
     * options: { frames = 5, timeoutMs = 25000, onProgress(count, total, hint) }
     * Resolves to { descriptor: number[128], frames: n } or rejects with an Error (user-facing message).
     */
    async function captureAverageDescriptor(video, options = {}) {
        const total = options.frames || 5;
        const timeoutMs = options.timeoutMs || 25000;
        const onProgress = options.onProgress || (() => {});
        const started = Date.now();
        const samples = [];

        onProgress(0, total, 'Hold still with eyes open…');

        while (samples.length < total) {
            if (Date.now() - started > timeoutMs) {
                throw new Error(`Only ${samples.length} of ${total} clear frames captured. Improve lighting, face the camera and try again.`);
            }

            const result = await describe(video);
            const quality = checkQuality(result, video.videoWidth);

            if (quality.ok && averageEar(result.landmarks) > 0.18) { // skip frames taken mid-blink
                const d = Array.from(result.descriptor);
                if (samples.length === 0 || euclidean(d, samples[0]) < 0.45) {
                    samples.push(d);
                    onProgress(samples.length, total, 'Hold still…');
                } else {
                    samples.length = 0;
                    onProgress(0, total, 'Face changed — restarting. Only one person in front of the camera.');
                }
            } else {
                onProgress(samples.length, total, quality.reason || 'Please keep your eyes open.');
            }

            await new Promise(r => setTimeout(r, 50)); // let the page repaint between frames
        }

        return { descriptor: averageDescriptors(samples), frames: samples.length };
    }

    /**
     * Signed head turn: nose-tip offset from the eye midpoint, relative to the eye distance.
     * Positive = the person turned to THEIR left (nose moves to the right of the raw camera image).
     * A flat photo tilted or rotated keeps this ratio almost unchanged, a real head does not.
     */
    function yaw(landmarks) {
        const centre = (pts) => ({ x: pts.reduce((s, p) => s + p.x, 0) / pts.length, y: pts.reduce((s, p) => s + p.y, 0) / pts.length });
        const l = centre(landmarks.getLeftEye()), r = centre(landmarks.getRightEye());
        return (landmarks.getNose()[3].x - (l.x + r.x) / 2) / dist(l, r);
    }

    /**
     * Liveness check ("turn your head to your LEFT/RIGHT, then look back at the camera").
     * Feed it one landmarks result per frame; `update()` returns true once the person looked
     * straight, turned to the requested side (held for TURN_FRAMES frames) and looked straight again.
     *
     * Why a head turn instead of a blink: on low-power kiosk PCs detection runs at ~2 fps, so a
     * blink falls between frames, and the tiny landmark model barely follows closing eyes. A turn is
     * held for about a second and the nose offset is measured reliably. The side is picked at random
     * so a pre-recorded video is unlikely to match. `onTurned` fires once the turn is confirmed.
     */
    class HeadTurnChallenge {
        static CENTRE = 0.12;       // |yaw| below this = looking straight at the camera (~ <14 degrees)
        static TURNED = 0.22;       // yaw beyond this in the asked direction = clear turn (~ 25 degrees)
        static TURN_FRAMES = 2;     // consecutive turned frames required
        static REPROMPT_MS = 12000; // pick a new side if the turn does not happen in time

        constructor(onTurned = null) { this.onTurned = onTurned; this.reset(); }

        reset() {
            this.direction = Math.random() < 0.5 ? 'left' : 'right';
            this.stage = 'centre';      // 'centre' -> 'turn' -> 'return' -> 'done'
            this.frontalFrames = 0;
            this.turnFrames = 0;
            this.promptAt = 0;
            this.wrongWay = false;
            this.lastYaw = null;
        }

        get passed() { return this.stage === 'done'; }

        update(landmarks) {
            const y = yaw(landmarks);
            this.lastYaw = y;
            const toward = this.direction === 'left' ? y : -y; // positive = turning the asked way
            const { CENTRE, TURNED, TURN_FRAMES, REPROMPT_MS } = HeadTurnChallenge;

            if (this.stage === 'centre') {
                this.frontalFrames = Math.abs(y) < CENTRE ? this.frontalFrames + 1 : 0;
                if (this.frontalFrames >= 2) { this.stage = 'turn'; this.promptAt = Date.now(); }
            } else if (this.stage === 'turn') {
                this.wrongWay = toward < -TURNED;
                this.turnFrames = toward > TURNED ? this.turnFrames + 1 : 0;
                if (this.turnFrames >= TURN_FRAMES) {
                    this.stage = 'return';
                    if (this.onTurned) this.onTurned();
                } else if (Date.now() - this.promptAt > REPROMPT_MS) {
                    const keep = this.onTurned;
                    this.reset(); // new random side; must face the camera again first
                    this.onTurned = keep;
                }
            } else if (this.stage === 'return' && Math.abs(y) < CENTRE) {
                this.stage = 'done';
            }
            return this.passed;
        }

        /** Short instruction for the current stage. */
        prompt() {
            const side = this.direction.toUpperCase();
            switch (this.stage) {
                case 'centre': return 'Look straight at the camera.';
                case 'turn': return this.wrongWay
                    ? `Other way — turn your head to your ${side}.`
                    : `Slowly turn your head to your ${side} and hold until the beep.`;
                case 'return': return 'Turn ✓ — now look straight at the camera.';
                default: return 'Liveness confirmed ✓';
            }
        }
    }

    /** Update the progress bar from the face-capture-guide partial, if it is on the page. */
    function showProgress(count, total, hint) {
        const wrap = document.getElementById('capture-progress');
        if (!wrap) return;
        wrap.classList.remove('hidden');
        document.getElementById('capture-progress-bar').style.width = `${Math.round((count / total) * 100)}%`;
        document.getElementById('capture-progress-count').textContent = `${count}/${total}`;
        document.getElementById('capture-progress-label').textContent =
            count >= total ? 'Face captured ✓' : (hint || 'Hold still with eyes open…');
    }

    /** Short confirmation tone (no audio file needed). Used when the head turn is confirmed. */
    let audioCtx = null;
    function beep(freq = 880, ms = 180) {
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + ms / 1000);
            osc.connect(gain).connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + ms / 1000);
        } catch (e) { /* no audio available — the on-screen prompt still changes */ }
    }

    /** Small frame-rate meter for ?facedebug=1. */
    function fpsMeter() {
        let last = performance.now(), fps = 0;
        return () => { const now = performance.now(); fps = fps * 0.8 + (1000 / (now - last)) * 0.2; last = now; return fps.toFixed(1); };
    }

    global.FaceUtils = {
        DEBUG, loadModels, detectFast, describe, checkQuality, captureAverageDescriptor,
        averageDescriptors, euclidean, eyeAspectRatio, averageEar, yaw, HeadTurnChallenge, showProgress, fpsMeter, beep,
    };
})(window);
