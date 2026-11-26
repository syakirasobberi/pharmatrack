<x-app-layout>
    <div class="py-8 sm:py-12 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-xl sm:text-2xl font-extrabold text-gray-800 mb-6">{{ __('Quick Patient Recognition') }}</h2>

            <div class="bg-white p-4 sm:p-8 rounded-3xl shadow-lg border border-gray-200">
                <p id="status" class="mb-4 text-teal-600 font-bold animate-pulse">{{ __('Initializing AI system...') }}</p>

                <div class="mb-4">
                    <label for="camera-select" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Select Camera') }}</label>
                    <select
                        id="camera-select"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md shadow-sm border"
                    >
                        <option value="">{{ __('Loading cameras...') }}</option>
                    </select>
                </div>

                <div id="video-container" class="relative inline-block w-full max-w-xl rounded-2xl overflow-hidden border-4 border-teal-100 shadow-inner bg-black">
                    <video id="video" width="480" height="360" autoplay muted playsinline class="w-full bg-black"></video>
                </div>

                {{-- Shown after a match: the pharmacist confirms the name before any record opens. --}}
                <div id="confirm-panel" class="hidden mt-6 rounded-2xl border-2 border-emerald-200 bg-emerald-50 p-5 text-left">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">{{ __('Possible match — please confirm') }}</p>
                    <p class="mt-1 text-xl font-extrabold text-gray-900" id="confirm-name"></p>
                    <p class="text-xs text-gray-500" id="confirm-detail"></p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a id="confirm-open" href="#" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700">{{ __('Yes, open this patient') }}</a>
                        <button id="confirm-reject" type="button" class="inline-flex items-center px-4 py-2 rounded-xl bg-white border border-gray-300 text-gray-700 font-bold hover:bg-gray-50">{{ __('Not this patient — scan again') }}</button>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-gray-500 text-sm">{{ __('Ask the patient to look directly at the camera with eyes open. Always check the name before opening a record.') }}</p>
                </div>
            </div>
        </div>
    </div>

    @include('partials.js-i18n')
    <script src="{{ asset('js/face-api.min.js') }}"></script>
    <script src="{{ asset('js/face-utils.js') }}?v={{ filemtime(public_path('js/face-utils.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const video = document.getElementById('video');
            const statusText = document.getElementById('status');
            const videoContainer = document.getElementById('video-container');
            const cameraSelect = document.getElementById('camera-select');
            const confirmPanel = document.getElementById('confirm-panel');

            // Matching happens on the server; this page never receives stored face data.
            const faceCount = {{ (int) $faceCount }};
            const identifyUrl = @json(route('pharmacist.quickScan.identify'));
            const csrfToken = @json(csrf_token());

            // Same rules as the kiosk: several clear, consistent frames before asking the server.
            const FRAMES_NEEDED = 3;
            const SAME_FACE_DISTANCE = 0.45;

            let canvas = null;
            let modelsReady = false;
            let running = false;     // scan loop active
            let paused = false;      // waiting for the pharmacist to confirm / retry
            let samples = [];

            try {
                if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    setStatus(t('Camera access is blocked because this page is not using HTTPS. Open PharmaTrack on https:// or localhost.'), 'error');
                    cameraSelect.innerHTML = '<option value="">' + t('HTTPS required for camera access') + '</option>';
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    setStatus(t('This browser does not allow camera access on the current page. Try HTTPS, localhost, or a supported browser.'), 'error');
                    cameraSelect.innerHTML = '<option value="">' + t('Camera API unavailable') + '</option>';
                    return;
                }

                if (faceCount === 0) {
                    setStatus(t('No usable patient face data found. Please register a face first.'), 'error');
                    return;
                }

                // Same detector + landmarks as registration, so the face fingerprints are comparable.
                await FaceUtils.loadModels(@json(asset('models')));
                modelsReady = true;

                await populateCameras();
                await startVideo(cameraSelect.value);

                cameraSelect.addEventListener('change', async (event) => {
                    await startVideo(event.target.value);
                });
            } catch (error) {
                console.error(error);
                const message = error && error.message ? error.message : '';

                if (message.includes('Permission denied') || message.includes('Permission dismissed') || message.includes('NotAllowedError')) {
                    setStatus(t('Camera permission was denied. Please allow camera access in the browser address bar and reload the page.'), 'error');
                } else if (message.includes('Requested device not found') || message.includes('NotFoundError')) {
                    setStatus(t('No camera was found. Please connect a webcam or phone camera and try again.'), 'error');
                } else {
                    setStatus(t('Face recognition failed to initialize. Please check camera permission, HTTPS, and model files.'), 'error');
                }
            }

            document.getElementById('confirm-reject').addEventListener('click', () => {
                confirmPanel.classList.add('hidden');
                resumeScanning(t('Scanning again...'));
            });

            async function populateCameras() {
                const permissionStream = await navigator.mediaDevices.getUserMedia({ video: true });
                permissionStream.getTracks().forEach((track) => track.stop());

                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter((device) => device.kind === 'videoinput');

                cameraSelect.innerHTML = '';

                if (videoDevices.length === 0) {
                    throw new Error('No video devices found.');
                }

                videoDevices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.deviceId;
                    option.text = device.label || t('Camera :number', { number: index + 1 });
                    cameraSelect.appendChild(option);
                });
            }

            async function startVideo(deviceId) {
                stopCurrentStream();

                const constraints = deviceId
                    ? { video: { deviceId: { exact: deviceId } } }
                    : { video: { facingMode: 'user' } };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                window.stream = stream;
                video.srcObject = stream;
                confirmPanel.classList.add('hidden');
                resumeScanning(t('System live: scanning for faces...'));
            }

            function stopCurrentStream() {
                if (window.stream) {
                    window.stream.getTracks().forEach((track) => track.stop());
                    window.stream = null;
                }
            }

            function resumeScanning(message) {
                samples = [];
                paused = false;
                setStatus(message, 'info');
            }

            function setStatus(message, state = 'info') {
                statusText.innerText = message;
                statusText.classList.remove('text-teal-600', 'text-red-500', 'text-green-600', 'animate-pulse');

                if (state === 'error') {
                    statusText.classList.add('text-red-500');
                    return;
                }

                if (state === 'success') {
                    statusText.classList.add('text-green-600');
                    return;
                }

                statusText.classList.add('text-teal-600');
            }

            function drawBox(result) {
                if (!canvas) {
                    canvas = faceapi.createCanvasFromMedia(video);
                    canvas.style.position = 'absolute';
                    canvas.style.top = '0';
                    canvas.style.left = '0';
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                    videoContainer.append(canvas);
                }

                const size = { width: video.videoWidth, height: video.videoHeight };
                faceapi.matchDimensions(canvas, size);
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                if (result) {
                    faceapi.draw.drawDetections(canvas, [faceapi.resizeResults(result.detection, size)]);
                }
            }

            async function identify(descriptors) {
                const response = await fetch(identifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ descriptors }),
                });

                if (response.status === 429) {
                    return { matched: false, reason: 'busy' };
                }

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            }

            function showConfirmation(result) {
                paused = true;
                document.getElementById('confirm-name').innerText = result.name;
                document.getElementById('confirm-detail').innerText =
                    t('Patient ID :id · match distance :distance (lower is closer; 0.45 is the limit)', { id: result.patient_id, distance: result.distance });
                document.getElementById('confirm-open').href = result.redirect;
                confirmPanel.classList.remove('hidden');
                setStatus(t('Possible match found. Check the name with the patient before opening.'), 'success');
            }

            async function scanLoop() {
                while (true) {
                    await new Promise((r) => setTimeout(r, 150));

                    if (!modelsReady || paused || !window.stream || video.readyState < 2) {
                        continue;
                    }

                    try {
                        const result = await FaceUtils.describe(video);
                        drawBox(result);

                        const quality = FaceUtils.checkQuality(result, video.videoWidth);
                        if (!quality.ok) {
                            samples = [];
                            setStatus(t(quality.reason), 'info');
                            continue;
                        }

                        if (FaceUtils.averageEar(result.landmarks) <= 0.18) {
                            setStatus(t('Please keep your eyes open.'), 'info');
                            continue;
                        }

                        const d = Array.from(result.descriptor);

                        // Frames must agree with each other; a different face restarts the capture.
                        if (samples.length && FaceUtils.euclidean(d, samples[0]) > SAME_FACE_DISTANCE) {
                            samples = [];
                        }

                        samples.push(d);
                        setStatus(t('Capturing... :count/:total — hold still', { count: samples.length, total: FRAMES_NEEDED }), 'info');

                        if (samples.length < FRAMES_NEEDED) {
                            continue;
                        }

                        const frames = samples;
                        samples = [];
                        paused = true;
                        setStatus(t('Checking...'), 'info');

                        const response = await identify(frames);

                        if (response.matched) {
                            showConfirmation(response);
                            continue;
                        }

                        const messages = {
                            ambiguous: t('Face matches more than one patient closely. Adjust lighting or angle and try again.'),
                            busy: t('Too many scans. Please wait a moment...'),
                        };
                        setStatus(messages[response.reason] || t('Face not recognized among your patients.'), 'error');
                        setTimeout(() => { if (confirmPanel.classList.contains('hidden')) { paused = false; } }, 1500);
                    } catch (error) {
                        console.error(error);
                        setStatus(t('Recognition failed. Check the connection and try again.'), 'error');
                        samples = [];
                        setTimeout(() => { paused = false; }, 2000);
                    }
                }
            }

            if (modelsReady && !running) {
                running = true;
                scanLoop();
            }
        });
    </script>
</x-app-layout>
