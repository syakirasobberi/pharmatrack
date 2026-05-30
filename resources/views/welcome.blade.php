<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaTrack — Smart Check-In</title>
    <meta name="description" content="PharmaTrack facial recognition kiosk. Walk up and scan your face to check in instantly.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --teal:     #0d9488;
            --teal-d:   #0f766e;
            --teal-l:   #14b8a6;
            --cyan:     #06b6d4;
            --bg:       #f0f4f8;
            --surface:  #ffffff;
            --surface2: #f8fafc;
            --border:   #e2e8f0;
            --border2:  #cbd5e1;
            --text:     #0f172a;
            --muted:    #64748b;
            --muted2:   #94a3b8;
            --shadow-sm: 0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
            --shadow:    0 4px 16px rgba(15,23,42,.08), 0 2px 6px rgba(15,23,42,.05);
            --shadow-lg: 0 12px 40px rgba(15,23,42,.1), 0 4px 14px rgba(15,23,42,.06);
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow: hidden;
        }

        /* ── Animated background ── */
        .bg-scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: linear-gradient(rgba(255,255,255,0.78), rgba(255,255,255,0.78)),
            url("{{ asset('storage/bgkiosk.png') }}");            
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
}
        .bg-dots {
            position: fixed; inset: 0; z-index: 0;
            background-image: radial-gradient(circle, rgba(13,148,136,.12) 1px, transparent 1px);
            background-size: 36px 36px;
            opacity: .6;
        }

        /* Soft floating blobs */
        .blob {
            position: fixed; border-radius: 50%; filter: blur(90px); z-index: 0; pointer-events: none;
            animation: blobFloat 10s ease-in-out infinite;
        }
        .blob-1 { width:500px; height:500px; top:-120px; left:-100px;  background:rgba(13,148,136,.08); animation-delay:0s; }
        .blob-2 { width:400px; height:400px; bottom:-100px; right:-80px; background:rgba(6,182,212,.07); animation-delay:-5s; }
        @keyframes blobFloat { 0%,100%{transform:translateY(0) scale(1)} 50%{transform:translateY(-18px) scale(1.02)} }

        /* ── Layout ── */
        .kiosk {
            position: relative; z-index: 1;
            display: flex; flex-direction: column;
            height: 100vh; padding: 20px 28px;
        }

        /* ── Header ── */
        .header {
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
        }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-icon {
            width: 42px; height: 42px; border-radius: 12px;
            background: linear-gradient(135deg, var(--teal), var(--cyan));
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(13,148,136,.3);
        }
        .logo-text { font-size: 1.2rem; font-weight: 800; letter-spacing: -.025em; color: var(--text); }
        .logo-sub  { font-size: .68rem; color: var(--muted2); letter-spacing: .09em; text-transform: uppercase; }

        .header-right { display: flex; align-items: center; gap: 10px; }
        #clock {
            font-size: .9rem; font-weight: 600; color: var(--muted);
            background: var(--surface); border: 1px solid var(--border);
            padding: 7px 16px; border-radius: 10px;
            box-shadow: var(--shadow-sm);
        }
        .staff-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 8px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600;
            border: 1px solid var(--border2); background: var(--surface);
            color: var(--muted); cursor: pointer; text-decoration: none;
            transition: all .2s; box-shadow: var(--shadow-sm);
        }
        .staff-btn:hover {
            background: var(--teal); color: #fff;
            border-color: var(--teal);
            box-shadow: 0 4px 14px rgba(13,148,136,.3);
        }

        /* ── Main content ── */
        .main {
            flex: 1; display: flex; align-items: center; justify-content: center;
            gap: 36px; min-height: 0;
        }

        /* ── Camera panel ── */
        .cam-panel {
            display: flex; flex-direction: column; align-items: center; gap: 14px;
        }
        .cam-frame {
            position: relative; width: 420px; height: 340px;
            border-radius: 24px; overflow: hidden;
            border: 2px solid rgba(13,148,136,.25);
            box-shadow: var(--shadow-lg), 0 0 0 6px rgba(13,148,136,.06);
            background: #000;
        }

        /* Corner brackets */
        .cam-frame::before, .cam-frame::after,
        .cam-frame .corner-br, .cam-frame .corner-bl {
            content: ''; position: absolute; width: 28px; height: 28px; z-index: 10;
        }
        .cam-frame::before  { top:0; left:0;  border-top:3px solid var(--teal-l); border-left:3px solid var(--teal-l); border-radius:4px 0 0 0; }
        .cam-frame::after   { top:0; right:0; border-top:3px solid var(--teal-l); border-right:3px solid var(--teal-l); border-radius:0 4px 0 0; }
        .cam-frame .corner-br { bottom:0; right:0; border-bottom:3px solid var(--teal-l); border-right:3px solid var(--teal-l); border-radius:0 0 4px 0; }
        .cam-frame .corner-bl { bottom:0; left:0; border-bottom:3px solid var(--teal-l); border-left:3px solid var(--teal-l); border-radius:0 0 0 4px; }

        /* scan line */
        .scan-line {
            position: absolute; left:0; right:0; height:2px; z-index: 9;
            background: linear-gradient(90deg, transparent, var(--teal-l), transparent);
            box-shadow: 0 0 10px var(--teal-l), 0 0 20px rgba(20,184,166,.4);
            animation: scanSweep 2.5s ease-in-out infinite;
        }
        @keyframes scanSweep { 0%{top:10%} 50%{top:90%} 100%{top:10%} }

        #video {
            width: 100%; height: 100%; object-fit: cover; background: #111;
            border-radius: 22px;
        }
        #overlay-canvas {
            position: absolute; top:0; left:0; pointer-events:none; z-index: 8;
        }

        /* Status badge */
        .status-badge {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 20px; border-radius: 30px;
            font-size: .82rem; font-weight: 600; color: var(--text);
            background: var(--surface); border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all .3s;
        }
        .status-dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
            background: var(--teal-l); animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
        .status-badge.error   { border-color: rgba(239,68,68,.3); background:#fff5f5; }
        .status-badge.error .status-dot { background: #ef4444; animation:none; }
        .status-badge.success { border-color: rgba(13,148,136,.4); background: #f0fdf9; }
        .status-badge.success .status-dot { background: #4ade80; animation:none; }

        /* Camera selector */
        .cam-select-wrap { display:flex; gap:8px; align-items:center; }
        .cam-select-wrap label { font-size:.75rem; color:var(--muted); font-weight:500; }
        #camera-select {
            background: var(--surface); border: 1px solid var(--border2);
            color: var(--text); border-radius:8px; padding:5px 10px;
            font-size:.8rem; font-family:inherit; cursor:pointer;
            box-shadow: var(--shadow-sm);
        }

        /* ── Info panel ── */
        .info-panel {
            max-width: 340px; display: flex; flex-direction: column; gap: 20px;
        }
        .info-headline {
            font-size: 2.1rem; font-weight: 800; line-height: 1.2; letter-spacing: -.03em;
            color: var(--text);
        }
        .info-headline span {
            background: linear-gradient(135deg, var(--teal), var(--cyan));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .info-sub { font-size: .92rem; color: var(--muted); line-height: 1.65; }

        .steps { display:flex; flex-direction:column; gap:10px; }
        .step {
            display:flex; align-items:center; gap:12px;
            padding: 12px 16px; border-radius:14px;
            background: var(--surface); border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: box-shadow .2s;
        }
        .step:hover { box-shadow: var(--shadow); }
        .step-num {
            width:30px; height:30px; border-radius:50%; flex-shrink:0;
            background: linear-gradient(135deg, var(--teal), var(--cyan));
            display:flex; align-items:center; justify-content:center;
            font-size:.75rem; font-weight:700; color:#fff;
            box-shadow: 0 3px 10px rgba(13,148,136,.3);
        }
        .step-text { font-size:.84rem; color: var(--muted); }
        .step-text strong { color: var(--text); font-weight:600; }

        .divider { display:flex; align-items:center; gap:10px; color:var(--muted2); font-size:.8rem; }
        .divider::before, .divider::after { content:''; flex:1; height:1px; background:var(--border); }

        .manual-btn {
            display:flex; align-items:center; justify-content:center; gap:8px;
            padding:12px; border-radius:14px; font-size:.88rem; font-weight:600;
            border: 1px solid var(--border2); background: var(--surface);
            color: var(--muted); cursor:pointer; text-decoration:none;
            transition: all .2s; box-shadow: var(--shadow-sm);
        }
        .manual-btn:hover {
            background: var(--teal); color: #fff;
            border-color: var(--teal);
            box-shadow: 0 4px 14px rgba(13,148,136,.28);
        }
        .manual-btn:hover svg { stroke: #fff; }

        /* ── Footer ── */
        .footer {
            flex-shrink:0; text-align:center;
            font-size:.7rem; color:var(--muted2); padding-top:8px;
        }

        /* ── Success overlay ── */
        #success-overlay {
            display:none; position:fixed; inset:0; z-index:100;
            background:rgba(240,244,248,.88); backdrop-filter:blur(14px);
            flex-direction:column; align-items:center; justify-content:center; gap:20px;
        }
        #success-overlay.show { display:flex; animation:fadeIn .3s ease; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        .success-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 28px; padding: 48px 52px;
            display: flex; flex-direction: column; align-items: center; gap: 16px;
            box-shadow: var(--shadow-lg);
            animation: cardIn .4s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes cardIn { from{transform:scale(.85);opacity:0} to{transform:scale(1);opacity:1} }
        .success-icon {
            width:80px; height:80px; border-radius:50%;
            background: linear-gradient(135deg, var(--teal), var(--cyan));
            display:flex; align-items:center; justify-content:center;
            font-size:2.5rem; color:#fff;
            box-shadow: 0 8px 32px rgba(13,148,136,.35);
        }
        .success-title { font-size:1.8rem; font-weight:800; color: var(--text); }
        .success-sub   { font-size:1rem; color: var(--muted); }
        .success-redirect { font-size:.85rem; color: var(--teal); font-weight:500; }
    </style>
</head>
<body>
    <div class="bg-scene"></div>
    <div class="bg-dots"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <!-- Success overlay -->
    <div id="success-overlay">
        <div class="success-card">
            <div class="success-icon">✓</div>
            <div class="success-title" id="success-name">Welcome!</div>
            <div class="success-sub">Patient identified successfully</div>
            <div class="success-redirect">Redirecting to your health portal…</div>
        </div>
    </div>

    <div class="kiosk">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <div class="logo-icon">💊</div>
                <div>
                    <div class="logo-text">PharmaTrack</div>
                    <div class="logo-sub">Smart Pharmacy System</div>
                </div>
            </div>
            <div class="header-right">
                <div id="clock">--:--:--</div>
                <a href="{{ route('login') }}" class="staff-btn" id="staff-login-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Staff Login
                </a>
            </div>
        </header>

        <!-- Main -->
        <main class="main">
            <!-- Info panel -->
            <div class="info-panel">
                <div>
                    <div class="info-headline">Hello!<br>Look at the<br><span>camera</span> to check in</div>
                    <div class="info-sub" style="margin-top:10px">Our AI will recognise you instantly — no cards, no passwords needed.</div>
                </div>
                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <div class="step-text"><strong>Face the camera</strong> directly</div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div class="step-text"><strong>Hold still</strong> for 1–2 seconds</div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div class="step-text">You'll be <strong>automatically redirected</strong></div>
                    </div>
                </div>
                <div class="divider">or</div>
                <a href="{{ route('login') }}" class="manual-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Login with email &amp; password
                </a>
            </div>

            <!-- Camera panel -->
            <div class="cam-panel">
                <div class="cam-frame" id="cam-frame">
                    <div class="corner-br"></div>
                    <div class="corner-bl"></div>
                    <div class="scan-line" id="scan-line"></div>
                    <video id="video" autoplay muted playsinline></video>
                    <canvas id="overlay-canvas"></canvas>
                </div>
                <div class="status-badge" id="status-badge">
                    <div class="status-dot" id="status-dot"></div>
                    <span id="status-text">Initializing camera…</span>
                </div>
                <div class="cam-select-wrap">
                    <label for="camera-select">Camera:</label>
                    <select id="camera-select"><option value="">Loading…</option></select>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            &copy; {{ date('Y') }} PharmaTrack &mdash; Facial recognition data is used only for patient identification.
        </footer>
    </div>

    <script src="{{ asset('js/face-api.min.js') }}"></script>
    <script>
    (async () => {
        // ── Clock ──
        const clockEl = document.getElementById('clock');
        const tick = () => {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('en-MY', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
        };
        tick(); setInterval(tick, 1000);

        // ── Elements ──
        const video      = document.getElementById('video');
        const canvas     = document.getElementById('overlay-canvas');
        const statusBadge= document.getElementById('status-badge');
        const statusText = document.getElementById('status-text');
        const statusDot  = document.getElementById('status-dot');
        const camSelect  = document.getElementById('camera-select');
        const camFrame   = document.getElementById('cam-frame');
        const scanLine   = document.getElementById('scan-line');
        const successOv  = document.getElementById('success-overlay');
        const successName= document.getElementById('success-name');

        const THRESHOLD  = 0.45;
        let faceMatcher  = null;
        let scanInterval = null;
        let redirecting  = false;

        // ── Load patients via API ──
        let registeredPatients = [];
        try {
            const res = await fetch('{{ route("kiosk.patients") }}');
            registeredPatients = await res.json();
        } catch (e) {
            setStatus('Could not load patient data.', 'error');
        }

        // ── Secure context check ──
        if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            setStatus('HTTPS required for camera access.', 'error');
            return;
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Camera API not available in this browser.', 'error');
            return;
        }

        // ── Load face-api models ──
        setStatus('Loading AI models…', 'info');
        try {
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri('{{ asset("models") }}'),
                faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset("models") }}'),
                faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset("models") }}'),
            ]);
        } catch (e) {
            setStatus('AI model files not found. Check public/models.', 'error');
            return;
        }

        // ── Build face matcher ──
        const labeled = registeredPatients.flatMap(p => {
            try {
                const d = JSON.parse(p.face_descriptor);
                if (!Array.isArray(d) || d.length !== 128) return [];
                return [new faceapi.LabeledFaceDescriptors(p.id.toString(), [new Float32Array(d)])];
            } catch { return []; }
        });

        if (labeled.length === 0) {
            setStatus('No patient face data registered yet.', 'error');
        } else {
            faceMatcher = new faceapi.FaceMatcher(labeled, THRESHOLD);
        }

        // ── Populate cameras ──
        try {
            const perm = await navigator.mediaDevices.getUserMedia({ video: true });
            perm.getTracks().forEach(t => t.stop());
            const devices = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
            camSelect.innerHTML = '';
            devices.forEach((d, i) => {
                const o = document.createElement('option');
                o.value = d.deviceId;
                o.textContent = d.label || `Camera ${i + 1}`;
                camSelect.appendChild(o);
            });
        } catch (e) {
            setStatus('Camera permission denied. Allow camera and reload.', 'error');
            return;
        }

        await startCamera(camSelect.value);
        camSelect.addEventListener('change', e => startCamera(e.target.value));

        // ── Start camera ──
        async function startCamera(deviceId) {
            stopCamera();
            if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const constraints = deviceId
                ? { video: { deviceId: { exact: deviceId } } }
                : { video: { facingMode: 'user' } };

            try {
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                window._kioskStream = stream;
                video.srcObject = stream;
                setStatus('System live — scanning for faces…', 'info');
            } catch (e) {
                setStatus('Could not start camera. Check permissions.', 'error');
            }
        }

        function stopCamera() {
            window._kioskStream?.getTracks().forEach(t => t.stop());
            window._kioskStream = null;
        }

        // ── Start scanning when video plays ──
        video.addEventListener('play', () => {
            canvas.width  = video.videoWidth  || 420;
            canvas.height = video.videoHeight || 340;
            canvas.style.position = 'absolute';
            canvas.style.top  = '0';
            canvas.style.left = '0';
            canvas.style.width  = '100%';
            canvas.style.height = '100%';

            const displaySize = { width: canvas.width, height: canvas.height };
            faceapi.matchDimensions(canvas, displaySize);

            let busy = false;
            scanInterval = setInterval(async () => {
                if (busy || !faceMatcher || redirecting) return;
                busy = true;

                const detection = await faceapi
                    .detectSingleFace(video)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                if (!detection) {
                    setStatus('No face detected — please look at the camera.', 'info');
                    busy = false;
                    return;
                }

                const resized = faceapi.resizeResults(detection, displaySize);

                // Draw detection box with teal colour
                const box = resized.detection.box;
                ctx.strokeStyle = '#14b8a6';
                ctx.lineWidth   = 2;
                ctx.strokeRect(box.x, box.y, box.width, box.height);

                const match = faceMatcher.findBestMatch(detection.descriptor);

                if (match.label !== 'unknown' && match.distance <= THRESHOLD) {
                    redirecting = true;
                    clearInterval(scanInterval);
                    setStatus('Patient identified! Redirecting…', 'success');
                    scanLine.style.display = 'none';

                    // Find patient name from list
                    const p = registeredPatients.find(x => x.id.toString() === match.label);
                    successName.textContent = p ? `Welcome, ${p.name}!` : 'Welcome!';
                    successOv.classList.add('show');

                    // POST to kiosk auth route to set session, then follow redirect
                    setTimeout(async () => {
                        try {
                            const res = await fetch(`/kiosk/auth/${match.label}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                            });
                            const data = await res.json();
                            window.location.href = data.redirect;
                        } catch (e) {
                            window.location.href = `/kiosk/summary/${match.label}`;
                        }
                    }, 2000);
                } else {
                    setStatus('Face detected — verifying identity…', 'info');
                }

                busy = false;
            }, 700);
        });

        // ── Helpers ──
        function setStatus(msg, state) {
            statusText.textContent = msg;
            statusBadge.className  = `status-badge ${state}`;
        }
    })();
    </script>
</body>
</html>
