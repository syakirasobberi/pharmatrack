<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Patient') }}
        </h2>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl sm:rounded-lg p-4 sm:p-6">
                
                <form id="patient-form" action="{{ route('pharmacist.patients.store') }}" method="POST">
                    @csrf
                    
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">Patient Details</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Age</label>
                            <input type="number" name="age" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gender</label>
                            <select name="gender" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold mb-4 border-b pb-2 text-gray-800">Initial Health Data</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Height (cm)</label>
                            <input type="number" step="0.01" name="height" id="height" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Weight (kg)</label>
                            <input type="number" step="0.01" name="weight" id="weight" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Calculated BMI</label>
                            <input type="text" id="bmi_display" readonly class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm cursor-not-allowed text-blue-700 font-bold" placeholder="Auto-calculated">
                        </div>
                    </div>


                    <div class="camera-section" style="margin-top: 20px; border: 1px solid #ccc; padding: 15px; border-radius: 8px;">
                        <h4 class="font-bold mb-2">Facial Biometric Registration <span class="text-sm font-normal text-gray-500">(Optional)</span></h4>
                        <p id="status" class="text-sm text-gray-600 mb-2">Loading AI models... You can save the patient without face data.</p>
                        
                        <div id="video-container" class="relative inline-block w-full max-w-sm overflow-hidden rounded-lg bg-black">
                            <video id="video" width="320" height="240" autoplay muted playsinline class="w-full rounded-lg"></video>
                        </div>

                        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                            <button type="button" id="btn-start-camera" class="px-4 py-2 bg-blue-600 text-white font-bold rounded shadow hover:bg-blue-700 transition-colors opacity-50 cursor-not-allowed" disabled>
                                Loading Models...
                            </button>
                            <button type="button" id="btn-scan-face" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded shadow hover:bg-indigo-700 transition-colors hidden" style="display: none;">
                                Scan & Capture Face
                            </button>
                        </div>

                        <input type="hidden" name="face_descriptor" id="hidden_face_descriptor">
                        
                        <p id="scan-success-msg" style="color: green; font-weight: bold; display: none; margin-top: 10px;">
                            Face successfully captured. You can now save the patient.
                        </p>
                    </div>

                    <div class="flex flex-col-reverse justify-end gap-3 mt-6 sm:flex-row sm:gap-4">
                        <a href="{{ route('pharmacist.dashboard') }}" class="inline-flex justify-center px-6 py-2 bg-gray-300 text-gray-800 font-bold rounded-lg hover:bg-gray-400 transition-colors">Cancel</a>
                        <button type="submit" class="inline-flex justify-center px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors shadow-md">Save Patient</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="{{ asset('js/face-api.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // ==========================================
            // 1. BMI CALCULATION LOGIC
            // ==========================================
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            const bmiDisplay = document.getElementById('bmi_display');

            function calculateBMI() {
                const heightCm = parseFloat(heightInput.value);
                const weightKg = parseFloat(weightInput.value);

                if (heightCm > 0 && weightKg > 0) {
                    const heightM = heightCm / 100;
                    const bmi = weightKg / (heightM * heightM);
                    bmiDisplay.value = bmi.toFixed(2);
                } else {
                    bmiDisplay.value = '';
                }
            }

            heightInput.addEventListener('input', calculateBMI);
            weightInput.addEventListener('input', calculateBMI);


            // ==========================================
            // 2. FACE RECOGNITION CAMERA LOGIC
            // ==========================================
            const video = document.getElementById('video');
            const statusText = document.getElementById('status');
            const hiddenInput = document.getElementById('hidden_face_descriptor');
            const successMsg = document.getElementById('scan-success-msg');
            const btnScanFace = document.getElementById('btn-scan-face');
            let previewCanvas = null;
            let previewInterval = null;

            if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                statusText.innerText = "Camera access is blocked because this page is not using HTTPS. Use https:// or localhost.";
                btnScanFace.disabled = true;
                btnScanFace.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                statusText.innerText = "This browser does not allow camera access on the current page. Try HTTPS, localhost, or a supported browser.";
                btnScanFace.disabled = true;
                btnScanFace.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            const btnStartCamera = document.getElementById('btn-start-camera');

            // Load AI Models from public/models folder
            Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
                faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                faceapi.nets.faceRecognitionNet.loadFromUri('/models')
            ]).then(() => {
                statusText.innerText = "AI Models ready. Face capture is optional. Click 'Start Camera' if you want to add face data.";
                btnStartCamera.disabled = false;
                btnStartCamera.innerText = "Start Camera";
                btnStartCamera.classList.remove('opacity-50', 'cursor-not-allowed');
            }).catch(err => {
                statusText.innerText = "Error loading AI models. Please check console.";
                console.error(err);
            });

            btnStartCamera.addEventListener('click', () => {
                btnStartCamera.style.display = 'none';
                btnScanFace.style.display = 'block';
                btnScanFace.classList.remove('hidden');
                startVideo();
            });

            // Start Webcam
            function startVideo() {
                statusText.innerText = "Camera ready. Please face the screen.";
                navigator.mediaDevices.getUserMedia({ video: {} })
                    .then(stream => video.srcObject = stream)
                    .catch(err => statusText.innerText = "Camera Error. Please allow camera access.");
            }

            video.addEventListener('play', () => {
                if (previewInterval) {
                    clearInterval(previewInterval);
                }

                if (previewCanvas) {
                    previewCanvas.remove();
                }

                previewCanvas = faceapi.createCanvasFromMedia(video);
                previewCanvas.style.position = 'absolute';
                previewCanvas.style.top = '0';
                previewCanvas.style.left = '0';

                document.getElementById('video-container').append(previewCanvas);
                const displaySize = { width: video.width, height: video.height };
                faceapi.matchDimensions(previewCanvas, displaySize);

                previewInterval = setInterval(async () => {
                    const detections = await faceapi.detectAllFaces(video);

                    previewCanvas.getContext('2d').clearRect(0, 0, previewCanvas.width, previewCanvas.height);

                    if (detections.length > 0) {
                        const resizedDetections = faceapi.resizeResults(detections, displaySize);
                        faceapi.draw.drawDetections(previewCanvas, resizedDetections);
                    }
                }, 300);
            });

            // Capture Face Action
            btnScanFace.addEventListener('click', async () => {
                const detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
                
                if (detections) {
                    // Convert AI array to standard JSON text and put it in the hidden input
                    const descriptorArray = Array.from(detections.descriptor);
                    hiddenInput.value = JSON.stringify(descriptorArray); 
                    
                    // Stop camera
                    const stream = video.srcObject;
                    if (stream) {
                        const tracks = stream.getTracks();
                        tracks.forEach(track => track.stop());
                    }
                    if (previewInterval) clearInterval(previewInterval);
                    if (previewCanvas) previewCanvas.remove();
                    video.style.display = 'none';
                    btnScanFace.style.display = 'none';

                    // Show success message
                    successMsg.style.display = "block";
                    statusText.innerText = "Face captured successfully. You can now save the patient.";
                    alert("Face captured successfully!");
                } else {
                    alert("Face not detected. Please look clearly at the camera.");
                }
            });
        });
    </script>
</x-app-layout>
