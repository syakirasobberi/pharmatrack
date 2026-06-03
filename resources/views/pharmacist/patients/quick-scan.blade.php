<x-app-layout>
    <div class="py-8 sm:py-12 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-xl sm:text-2xl font-extrabold text-gray-800 mb-6">Quick Patient Recognition</h2>

            <div class="bg-white p-4 sm:p-8 rounded-3xl shadow-lg border border-gray-200">
                <p id="status" class="mb-4 text-blue-600 font-bold animate-pulse">Initializing AI system...</p>

                <div class="mb-4">
                    <label for="camera-select" class="block text-sm font-medium text-gray-700 mb-1">Select Camera</label>
                    <select
                        id="camera-select"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md shadow-sm border"
                    >
                        <option value="">Loading cameras...</option>
                    </select>
                </div>

                <div id="video-container" class="relative inline-block w-full max-w-xl rounded-2xl overflow-hidden border-4 border-blue-100 shadow-inner bg-black">
                    <video id="video" width="480" height="360" autoplay muted playsinline class="w-full bg-black"></video>
                </div>

                <div class="mt-6">
                    <p class="text-gray-500 text-sm">Please ask the patient to look directly at the camera for automatic recognition.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/face-api.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const video = document.getElementById('video');
            const statusText = document.getElementById('status');
            const videoContainer = document.getElementById('video-container');
            const cameraSelect = document.getElementById('camera-select');
            const registeredPatients = @json($patients);
            const matchThreshold = 0.45;

            let faceMatcher = null;
            let canvas = null;
            let scanInterval = null;

            try {
                if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    setStatus('Camera access is blocked because this page is not using HTTPS. Open PharmaTrack on https:// or localhost.', 'error');
                    cameraSelect.innerHTML = '<option value="">HTTPS required for camera access</option>';
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    setStatus('This browser does not allow camera access on the current page. Try HTTPS, localhost, or a supported browser.', 'error');
                    cameraSelect.innerHTML = '<option value="">Camera API unavailable</option>';
                    return;
                }

                await Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri('{{ asset("models") }}'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset("models") }}'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset("models") }}'),
                ]);

                const labeledDescriptors = registeredPatients.flatMap((patient) => {
                    try {
                        const parsedDescriptor = JSON.parse(patient.face_descriptor);

                        if (!Array.isArray(parsedDescriptor) || parsedDescriptor.length !== 128) {
                            return [];
                        }

                        return [
                            new faceapi.LabeledFaceDescriptors(
                                patient.id.toString(),
                                [new Float32Array(parsedDescriptor)]
                            ),
                        ];
                    } catch (error) {
                        console.warn('Skipping invalid face descriptor for patient', patient.id, error);
                        return [];
                    }
                });

                if (labeledDescriptors.length === 0) {
                    setStatus('No usable patient face data found. Please register a face first.', 'error');
                    return;
                }

                faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, matchThreshold);

                await populateCameras();
                await startVideo(cameraSelect.value);

                cameraSelect.addEventListener('change', async (event) => {
                    await startVideo(event.target.value);
                });
            } catch (error) {
                console.error(error);
                const message = error && error.message ? error.message : '';

                if (message.includes('Permission denied') || message.includes('Permission dismissed') || message.includes('NotAllowedError')) {
                    setStatus('Camera permission was denied. Please allow camera access in the browser address bar and reload the page.', 'error');
                } else if (message.includes('Requested device not found') || message.includes('NotFoundError')) {
                    setStatus('No camera was found. Please connect a webcam or phone camera and try again.', 'error');
                } else {
                    setStatus('Face recognition failed to initialize. Please check camera permission, HTTPS, and model files.', 'error');
                }
            }

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
                    option.text = device.label || `Camera ${index + 1}`;
                    cameraSelect.appendChild(option);
                });
            }

            async function startVideo(deviceId) {
                stopCurrentStream();
                resetScannerOverlay();

                const constraints = deviceId
                    ? { video: { deviceId: { exact: deviceId } } }
                    : { video: { facingMode: 'user' } };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                window.stream = stream;
                video.srcObject = stream;
                setStatus('System live: scanning for faces...', 'info');
            }

            function stopCurrentStream() {
                if (window.stream) {
                    window.stream.getTracks().forEach((track) => track.stop());
                    window.stream = null;
                }
            }

            function resetScannerOverlay() {
                if (scanInterval) {
                    clearInterval(scanInterval);
                    scanInterval = null;
                }

                if (canvas) {
                    canvas.remove();
                    canvas = null;
                }
            }

            function setStatus(message, state = 'info') {
                statusText.innerText = message;
                statusText.classList.remove('text-blue-600', 'text-red-500', 'text-green-600');

                if (state === 'error') {
                    statusText.classList.add('text-red-500');
                    return;
                }

                if (state === 'success') {
                    statusText.classList.add('text-green-600');
                    return;
                }

                statusText.classList.add('text-blue-600');
            }

            video.addEventListener('play', () => {
                resetScannerOverlay();

                canvas = faceapi.createCanvasFromMedia(video);
                canvas.style.position = 'absolute';
                canvas.style.top = '0';
                canvas.style.left = '0';
                videoContainer.append(canvas);

                const displaySize = { width: video.width, height: video.height };
                faceapi.matchDimensions(canvas, displaySize);

                let isProcessing = false;

                scanInterval = setInterval(async () => {
                    if (isProcessing || !faceMatcher) {
                        return;
                    }

                    const detection = await faceapi
                        .detectSingleFace(video)
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                    if (!detection) {
                        setStatus('No face detected. Please position the face inside the frame.', 'info');
                        return;
                    }

                    const resizedDetection = faceapi.resizeResults(detection, displaySize);
                    faceapi.draw.drawDetections(canvas, [resizedDetection]);

                    const result = faceMatcher.findBestMatch(detection.descriptor);

                    if (result.label !== 'unknown' && result.distance <= matchThreshold) {
                        isProcessing = true;
                        setStatus('Patient identified. Redirecting...', 'success');
                        window.location.href = `{{ url('/pharmacist/patients') }}/${result.label}/summary`;
                        return;
                    }

                    setStatus(`Face not recognized. Current match: ${result.label} (${result.distance.toFixed(2)})`, 'error');
                }, 600);
            });
        });
    </script>
</x-app-layout>
