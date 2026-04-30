<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaTrack Camera Test</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-family: Arial, sans-serif;
            margin-top: 50px;
        }
        #video-container {
            position: relative;
            margin-top: 20px;
        }
        /* Canvas for drawing the detection box over the face */
        canvas {
            position: absolute;
            top: 0;
            left: 0;
        }
        .btn-back {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
    </style>
    <script src="{{ asset('js/face-api.min.js') }}"></script>
</head>
<body>

    <h2>PharmaTrack Facial Recognition Test</h2>
    <p id="status">Loading AI models... Please wait a moment.</p>

    <div id="video-container">
        <video id="video" width="720" height="560" autoplay muted></video>
    </div>

    <a href="{{ url('/') }}" class="btn-back">Back to Home</a>
<button id="btn-save-face" style="margin-top:20px; padding:10px; background:green; color:white; border-radius:5px; border:none; cursor:pointer;">
    Register Face
</button>
    <script>
        const video = document.getElementById('video');
        const statusText = document.getElementById('status');

        // 1. Load AI Models from the public/models folder
        Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri('/models')
        ]).then(startVideo);

        // 2. Start the Camera
        function startVideo() {
            statusText.innerText = "Models ready! Please 'Allow' camera access in your browser.";
            
            navigator.mediaDevices.getUserMedia({ video: {} })
                .then(stream => {
                    video.srcObject = stream;
                    statusText.innerText = "Camera successfully accessed. Please position your face in view.";
                })
                .catch(err => {
                    console.error("Camera error: ", err);
                    statusText.innerText = "Error: Camera cannot be accessed. Please check your permissions.";
                });
        }

        // 3. Real-time Face Detection
        video.addEventListener('play', () => {
            const canvas = faceapi.createCanvasFromMedia(video);
            document.getElementById('video-container').append(canvas);
            
            const displaySize = { width: video.width, height: video.height };
            faceapi.matchDimensions(canvas, displaySize);

            setInterval(async () => {
                const detections = await faceapi.detectAllFaces(video)
                                                .withFaceLandmarks()
                                                .withFaceDescriptors();
                
                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                faceapi.draw.drawDetections(canvas, resizedDetections);
            }, 100);
        });

        document.getElementById('register-btn').addEventListener('click', async () => {
    // Pastikan wajah dikesan dahulu
    const detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();

    if (detections) {
        const descriptor = detections.descriptor; // Ini adalah Float32Array (128 nombor)
        const patientId = 1; // Contoh ID pesakit buat sementara waktu

        // Hantar data ke Laravel Controller
        fetch('/save-face-descriptor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}' // Penting untuk keselamatan Laravel
            },
            body: JSON.stringify({
                patient_id: patientId,
                descriptor: Array.from(descriptor) // Tukar array AI ke array biasa
            })
        })
        .then(response => response.json())
        .then(data => alert('Face successfully registered!'))
        .catch(error => console.error('Error:', error));
    } else {
        alert("Face not detected. Please try again.");
    }
});
    </script>

</body>
</html>