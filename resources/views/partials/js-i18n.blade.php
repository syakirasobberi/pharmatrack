{{--
    EN / BM for messages built in JavaScript on the pharmacist pages.
    Defines window.t(message, vars): looks the English text up in the dictionary below
    (filled from lang/ms.json via __()) and replaces :placeholders. Unknown text stays as is.
--}}
@php
    $__jsKeys = [
        // Camera / face capture (register patient, update face, quick scan)
        'Camera access is blocked because this page is not using HTTPS. Use https:// or localhost.',
        'Camera access is blocked because this page is not using HTTPS. Open PharmaTrack on https:// or localhost.',
        'This browser does not allow camera access on the current page. Try HTTPS, localhost, or a supported browser.',
        "AI Models ready. Face capture is optional. Click 'Start Camera' if you want to add face data.",
        'Start Camera',
        'Error loading AI models. Please check console.',
        "Tick 'Patient consent' first: the patient must agree before their face is captured.",
        'Tick "Patient consent" first: the patient must agree before their face is captured.',
        'Camera ready. Please face the screen.',
        'Camera Error. Please allow camera access.',
        'Capturing :count/:total…',
        'Face captured successfully from :frames frames. You can now save the patient.',
        'AI models ready.',
        'Failed to load AI models.',
        'Loading models… please wait',
        'Starting camera…',
        "Camera live — position patient's face in frame.",
        'Camera error. Please allow camera access.',
        'Face detected (:count). Click "Capture & Save Face" when ready.',
        'No face detected — position patient in front of camera.',
        'Saving…',
        'Capture & Save Face',
        'Failed to save face data. Please try again.',
        'HTTPS required for camera access',
        'Camera API unavailable',
        'No usable patient face data found. Please register a face first.',
        'Camera permission was denied. Please allow camera access in the browser address bar and reload the page.',
        'No camera was found. Please connect a webcam or phone camera and try again.',
        'Face recognition failed to initialize. Please check camera permission, HTTPS, and model files.',
        'Scanning again...',
        'Camera :number',
        'System live: scanning for faces...',
        'Patient ID :id · match distance :distance (lower is closer; 0.45 is the limit)',
        'Possible match found. Check the name with the patient before opening.',
        'Please keep your eyes open.',
        'Capturing... :count/:total — hold still',
        'Checking...',
        'Face matches more than one patient closely. Adjust lighting or angle and try again.',
        'Too many scans. Please wait a moment...',
        'Face not recognized among your patients.',
        'Recognition failed. Check the connection and try again.',
        // Hints produced by public/js/face-utils.js
        'No face detected — please look at the camera.',
        'Face unclear — improve lighting and hold still.',
        'Please move closer to the camera.',
        'Please face the camera directly.',
        'Hold still with eyes open…',
        'Hold still…',
        'Face changed — restarting. Only one person in front of the camera.',
        'Face captured ✓',
        // Charts
        'Blood Sugar (mmol/L)',
        'Cholesterol (mmol/L)',
        'HbA1c (%)',
        'LDL (mmol/L)',
        'BMI',
        'Blood Sugar',
        'Blood Pressure',
        'Cholesterol',
        'Patient Health Profile',
        // AI suggestion (summary page)
        'Generating...',
        'Analyzing latest check-up metrics...',
        'Unable to generate suggestion.',
        'Unable to generate API suggestion right now.',
        'Regenerate AI Suggestion',
        // Check-up report reading (record check-up page)
        'Preparing PDF page :n of :count...',
        'Please choose an image or PDF first.',
        'Reading report... this may take a few seconds.',
        'Page :n of :count',
        'Preparing image...',
        'Image report',
        'No text detected.',
        'Smart Extract: sending the report to the AI... this can take up to 30 seconds.',
        'The file is larger than 10 MB. Please upload a smaller PDF or a photo.',
        'Too many Smart Extract requests. Please wait a minute and try again.',
        'The file is too large for the server. Please upload a smaller file.',
        'Smart Extract failed. Please try again or use Extract Readings.',
        'Smart Extract (AI)',
        'Smart Extract failed. Please check your connection, or use Extract Readings.',
        'Error: :message',
        'N/A',
        'Could not read the report. Try Smart Extract (AI), a clearer image, or enter values manually.',
        'Could not read the report. Try a clearer image, or enter values manually.',
        ':source finished, but no clear readings were found. Try Smart Extract (AI), a clearer image, or enter values manually.',
        ':source finished, but no clear readings were found. Try a clearer image, or enter values manually.',
        'Values extracted (:source) on :date — please verify before saving.',
        '✓ :source: filled :fields. Please review before saving.',
        'OCR library is still loading.',
        'PDF text',
        'BP', 'blood sugar', 'haemoglobin', 'cholesterol', 'A/G ratio', 'sodium', 'urine glucose', 'heart rate',
        'Only :count of :total clear frames captured. Improve lighting, face the camera and try again.',
    ];
    $__jsDict = collect($__jsKeys)->mapWithKeys(fn ($k) => [$k => __($k)]);
@endphp
<script>
    window.T = Object.assign(window.T || {}, @json($__jsDict));
    window.t = window.t || function (msg, vars = {}) {
        let out = (window.T && window.T[msg] !== undefined) ? window.T[msg] : msg;
        for (const [k, v] of Object.entries(vars)) out = out.split(':' + k).join(String(v));
        return out;
    };
</script>
