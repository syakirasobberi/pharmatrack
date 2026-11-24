/**
 * PharmaTrack face helpers (requires face-api.js to be loaded first).
 *
 * Two pipelines, tuned for ordinary laptops / kiosk PCs without a strong GPU:
 *  - FAST     (tiny detector + tiny landmarks): every frame, for the blink check and quality hints.
 *  - DESCRIBE (tiny detector + full landmarks + recognition net): only on the few frames used for
 *             identification / registration. Registration and kiosk use the same pipeline so their
 *             face fingerprints are directly comparable.
 *
 *  - FaceUtils.loadModels()                load everything both pipelines need
 *  - FaceUtils.detectFast() / describe()   run one frame through a pipeline
 *  - FaceUtils.checkQuality()              rejects unclear / small / side-on faces
 *  - FaceUtils.captureAverageDescriptor()  multi-frame registration: averages several good frames
 *  - FaceUtils.BlinkDetector               liveness check from the eye landmarks
 *
 * Add ?facedebug=1 to the page URL to see frame rate and eye-openness values in the status text.
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
     */
    function checkQuality(result, videoWidth) {
        if (!result) return { ok: false, reason: 'No face detected — please look at the camera.' };

        const { score, box } = result.detection;
        if (score < MIN_SCORE) return { ok: false, reason: 'Face unclear — improve lighting and hold still.' };
        if (videoWidth && box.width < videoWidth * MIN_FACE_RATIO) return { ok: false, reason: 'Please move closer to the camera.' };

        const lm = result.landmarks;
        const centre = (pts) => ({ x: pts.reduce((s, p) => s + p.x, 0) / pts.length, y: pts.reduce((s, p) => s + p.y, 0) / pts.length });
        const l = centre(lm.getLeftEye()), r = centre(lm.getRightEye());
        const eyeMidX = (l.x + r.x) / 2;
        const noseTip = lm.getNose()[3];
        if (Math.abs(noseTip.x - eyeMidX) / dist(l, r) > MAX_YAW_RATIO) {
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
     * Liveness check ("close your eyes until the beep, then open them"): feed it one landmarks result
     * per frame; `update()` returns true once the eyes were seen open → closed → open again.
     *
     * Why not a quick blink: on low-power kiosk PCs face detection runs at ~2 fps, so a 0.2 s blink
     * falls between frames. Asking the person to keep their eyes closed until the kiosk beeps
     * guarantees at least one "closed" frame. `onClosed` fires once when the closure is confirmed.
     * The open-eye baseline adapts to each person (narrow or wide eyes).
     */
    class BlinkDetector {
        constructor(onClosed = null) { this.onClosed = onClosed; this.reset(); }

        reset() {
            this.baseline = null;
            this.openFrames = 0;
            this.closed = false;
            this.closedAt = 0;
            this.blinked = false;
            this.lastEar = null;
        }

        update(landmarks) {
            const ear = averageEar(landmarks);
            this.lastEar = ear;

            if (this.baseline === null) { this.baseline = ear; this.openFrames = 1; return this.blinked; }

            const closedThreshold = this.baseline * 0.72;
            const openThreshold = this.baseline * 0.88;

            // Need a couple of open-eye frames first so the baseline is trustworthy.
            if (!this.closed && ear < closedThreshold && this.openFrames >= 2) {
                this.closed = true;
                this.closedAt = Date.now();
                if (this.onClosed) this.onClosed();
            } else if (this.closed && ear > openThreshold) {
                if (Date.now() - this.closedAt < 8000) this.blinked = true;
                this.closed = false;
            }

            if (!this.closed && ear > openThreshold) this.openFrames++;

            if (!this.closed && ear > closedThreshold) {
                this.baseline = this.baseline * 0.85 + ear * 0.15;
            }
            return this.blinked;
        }

        eyesOpen() {
            return this.lastEar !== null && this.baseline !== null && this.lastEar > this.baseline * 0.85;
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

    /** Short confirmation tone (no audio file needed). Used when closed eyes are detected. */
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
        averageDescriptors, euclidean, eyeAspectRatio, averageEar, BlinkDetector, showProgress, fpsMeter, beep,
    };
})(window);
