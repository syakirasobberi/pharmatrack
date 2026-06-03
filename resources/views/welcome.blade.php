<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PharmaTrack — Smart Check-In</title>
    <meta name="description" content="PharmaTrack facial recognition kiosk. Walk up and scan your face to check in instantly.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:    #0284c7; /* Deep Medical Blue */
            --primary-l:  #0ea5e9; /* Light Blue */
            --secondary:  #059669; /* Success Green */
            --bg:         #f8fafc; /* Crisp Light Gray/Blue */
            --surface:    rgba(255, 255, 255, 0.75);
            --surface-solid: #ffffff;
            --border:     rgba(14, 165, 233, 0.15);
            --border-hover: rgba(14, 165, 233, 0.3);
            --text:       #0f172a; /* Slate Dark */
            --muted:      #64748b; /* Slate Gray */
            --shadow-sm:  0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg:  0 12px 32px rgba(14, 165, 233, 0.08);
            --glass-blur: blur(24px);
        }

        html, body {
            min-height: 100vh;
            height: auto;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* ── Medical Animated Background (Light Mode) ── */
        /* ── Advanced Medical Animated Background ── */
        .bg-scene {
            position: fixed; inset: 0; z-index: 0;
            background-color: var(--bg);
        }

        /* Subtle technical dot grid */
        .bg-grid {
            position: fixed; inset: 0; z-index: 0;
            background-image: radial-gradient(rgba(14, 165, 233, 0.15) 1px, transparent 1px);
            background-size: 32px 32px;
            opacity: 0.6;
        }

        /* Ambient floating orbs */
        .bg-blob {
            position: fixed; border-radius: 50%; 
            filter: blur(80px); z-index: 0; 
            pointer-events: none; opacity: 0.4;
            animation: float 12s infinite ease-in-out alternate;
        }
        
        .blob-1 {
            width: 500px; height: 500px;
            background: rgba(14, 165, 233, 0.4); /* Primary blue */
            top: -100px; left: -100px;
        }

        .blob-2 {
            width: 600px; height: 600px;
            background: rgba(5, 150, 105, 0.2); /* Secondary green */
            bottom: -150px; right: -100px;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, -40px) scale(1.1); }
        }
        
        .ekg-container {
            position: fixed; top: 50%; left: 0; width: 100%; height: 250px;
            transform: translateY(-50%); z-index: 0; opacity: 0.06;
            pointer-events: none;
        }

        .ekg-line {
            fill: none; stroke: var(--primary); stroke-width: 3;
            stroke-linecap: round; stroke-linejoin: round;
            stroke-dasharray: 3000; stroke-dashoffset: 3000;
            animation: drawEkg 8s linear infinite;
        }

        @keyframes drawEkg {
            0% { stroke-dashoffset: 3000; }
            50% { stroke-dashoffset: 0; }
            100% { stroke-dashoffset: -3000; }
        }
        
        .ekg-container {
            position: fixed; top: 50%; left: 0; width: 100%; height: 200px;
            transform: translateY(-50%); z-index: 0; opacity: 0.08;
            pointer-events: none;
        }

        .ekg-line {
            fill: none;
            stroke: var(--primary);
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 3000;
            stroke-dashoffset: 3000;
            animation: drawEkg 6s linear infinite;
        }

        @keyframes drawEkg {
            0% { stroke-dashoffset: 3000; }
            50% { stroke-dashoffset: 0; }
            100% { stroke-dashoffset: -3000; }
        }

        /* ── Modern Glassmorphism Layout ── */
        .kiosk, #welcome-screen {
            position: relative; z-index: 1;
            display: flex; flex-direction: column;
            min-height: 100dvh; padding: 24px 32px;
        }

        .header {
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0; z-index: 10; gap: 16px;
        }

        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon {
            width: 48px; height: 48px; border-radius: 14px;
            background: linear-gradient(135deg, var(--primary-l), var(--primary));
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
        }
        .logo-text { font-size: 1.3rem; font-weight: 800; letter-spacing: -0.02em; color: var(--text); }
        .logo-sub  { font-size: 0.7rem; color: var(--primary); font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }

        #clock {
            font-size: 0.95rem; font-weight: 700; color: var(--text);
            background: var(--surface); backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border); padding: 10px 20px; border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        /* ── Welcome Screen Enhancements ── */
        .welcome-main {
            flex: 1; display: flex; flex-direction: column; align-items: center; gap: 64px;
            padding: 40px 0 60px 0;
        }

        .welcome-hero {
            display: flex; align-items: stretch; justify-content: center; gap: 32px;
            width: 100%; max-width: 1000px;
        }

        .glass-card {
            background: var(--surface); backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border); border-radius: 28px;
            padding: 40px; box-shadow: var(--shadow-lg);
            display: flex; flex-direction: column;
            animation: floatUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .welcome-card { flex: 1.2; background: var(--surface-solid); min-width: 300px; }
        .about-card { 
            flex: 1; min-width: 300px;
            background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(240,249,255,0.8));
        }

        @keyframes floatUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .section-headline { font-size: clamp(1.6rem, 4vw, 2rem); font-weight: 800; line-height: 1.2; margin-bottom: 16px; color: var(--text); }
        .section-headline span { color: var(--primary); }
        .section-desc { font-size: 0.95rem; color: var(--muted); line-height: 1.6; margin-bottom: 24px; }

        .badge {
            display: inline-block; padding: 6px 14px; border-radius: 20px;
            background: rgba(14, 165, 233, 0.1); color: var(--primary);
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 16px;
            align-self: flex-start;
        }

        .btn-start {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            width: 100%; padding: 16px 24px; border-radius: 16px;
            font-size: 1.05rem; font-weight: 700; cursor: pointer; border: none;
            background: linear-gradient(135deg, var(--primary-l), var(--primary)); color: #fff;
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.25); transition: all 0.3s ease;
        }
        .btn-start:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(14, 165, 233, 0.35); }
        
        .login-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
        
        .btn-staff {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px; border-radius: 14px; font-size: 0.9rem; font-weight: 600;
            border: 1px solid var(--border); background: var(--surface-solid);
            color: var(--text); text-decoration: none; transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }
        .btn-staff:hover { background: var(--bg); border-color: var(--border-hover); color: var(--primary); }

        /* ── Registration Banner ── */
        .registration-banner {
            width: 100%; max-width: 1100px;
            background: #f0f9ff; border: 1px solid #bae6fd;
            border-radius: 20px; padding: 24px 32px;
            display: flex; align-items: center; gap: 20px;
            box-shadow: var(--shadow-sm); margin-top: 8px;
        }

        .registration-icon {
            flex-shrink: 0; width: 48px; height: 48px; border-radius: 14px;
            background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
        }

        .registration-content h3 { font-size: 1.05rem; font-weight: 700; color: #0369a1; margin-bottom: 4px; }
        .registration-content p { font-size: 0.9rem; color: #075985; line-height: 1.5; }

        @media (max-width: 600px) {
            .registration-banner { flex-direction: column; text-align: center; padding: 24px; }
        }

        /* ── "How it Works" Steps Section ── */
        .content-section {
            width: 100%; max-width: 1100px;
            display: flex; flex-direction: column; align-items: center; gap: 32px;
            animation: floatUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        
        .section-header { text-align: center; margin-bottom: 8px; }
        .section-header h2 { font-size: 1.8rem; font-weight: 800; color: var(--text); letter-spacing: -0.02em; }
        .section-header span { color: var(--primary); }

        .steps-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px; width: 100%;
        }

        .step-card {
            background: var(--surface-solid); border: 1px solid var(--border);
            border-radius: 24px; padding: 32px 24px; display: flex; flex-direction: column;
            align-items: center; text-align: center; box-shadow: var(--shadow-sm);
            position: relative; overflow: hidden;
        }

        .step-number {
            width: 48px; height: 48px; border-radius: 50%; margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary-l), var(--primary));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 800; color: #fff;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        .step-title { font-size: 1.15rem; font-weight: 700; color: var(--text); margin-bottom: 12px; }
        .step-desc { font-size: 0.9rem; color: var(--muted); line-height: 1.5; }

        /* ── "Why PharmaTrack?" Visual Section ── */
        .features-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px; width: 100%;
        }

        .feature-card {
            background: var(--surface); backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border); border-radius: 24px;
            padding: 32px 24px; display: flex; flex-direction: column; align-items: center;
            text-align: center; transition: all 0.3s ease; box-shadow: var(--shadow-sm);
        }
        
        .feature-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--primary-l); }

        .feature-icon-wrapper {
            width: 64px; height: 64px; border-radius: 16px; margin-bottom: 20px;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(2, 132, 199, 0.15));
            display: flex; align-items: center; justify-content: center;
            color: var(--primary); border: 1px solid rgba(14, 165, 233, 0.2);
        }

        /* ── Privacy Banner ── */
        .privacy-banner {
            width: 100%; max-width: 1100px;
            background: #f0fdf4; border: 1px solid #bbf7d0;
            border-radius: 20px; padding: 24px 32px;
            display: flex; align-items: center; gap: 20px;
            box-shadow: var(--shadow-sm); margin-top: 16px;
        }

        .privacy-icon {
            flex-shrink: 0; width: 48px; height: 48px; border-radius: 14px;
            background: var(--secondary); color: #fff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
        }

        .privacy-content h3 { font-size: 1.05rem; font-weight: 700; color: #065f46; margin-bottom: 4px; }
        .privacy-content p { font-size: 0.9rem; color: #047857; line-height: 1.5; }

        /* ── Kiosk Main Content ── */
        .kiosk { display: none; }
        .main { flex: 1; display: flex; align-items: center; justify-content: center; gap: 48px; padding: 24px 0; }

        .cam-panel { display: flex; flex-direction: column; align-items: center; gap: 20px; width: 100%; max-width: 480px; }
        .cam-frame {
            position: relative; width: 100%; aspect-ratio: 4/3;
            border-radius: 28px; overflow: hidden; border: 1px solid var(--border);
            box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.5), var(--shadow-lg); background: #000;
        }
        
        .cam-frame::after {
            content: ''; position: absolute; inset: 16px;
            border: 2px solid rgba(14, 165, 233, 0.5); border-radius: 16px; pointer-events: none;
        }

        .scan-line {
            position: absolute; left: 0; right: 0; height: 3px; z-index: 9;
            background: linear-gradient(90deg, transparent, var(--primary-l), transparent);
            box-shadow: 0 0 16px var(--primary-l); animation: scanSweep 3s ease-in-out infinite;
        }
        @keyframes scanSweep { 0% { top: 10%; opacity: 0; } 10% { opacity: 1; } 50% { top: 90%; } 90% { opacity: 1; } 100% { top: 10%; opacity: 0; } }

        #video { width: 100%; height: 100%; object-fit: cover; border-radius: 28px; background: #e2e8f0; }
        #overlay-canvas { position: absolute; top: 0; left: 0; pointer-events: none; z-index: 8; width: 100%; height: 100%; }

        .status-badge {
            display: flex; align-items: center; gap: 10px; padding: 12px 24px; border-radius: 30px;
            font-size: 0.9rem; font-weight: 600; background: var(--surface-solid);
            border: 1px solid var(--border); box-shadow: var(--shadow-sm); color: var(--text);
        }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--primary); animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.8); } }
        
        .status-badge.success .status-dot { background: var(--secondary); animation: none; }
        .status-badge.error .status-dot { background: #ef4444; animation: none; }
        .status-badge.error { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }

        /* ── Timeout Bar ── */
        .timeout-wrap { width: 100%; max-width: 300px; display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--muted); font-weight: 600; }
        #timeout-bar { flex: 1; height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
        #timeout-fill { height: 100%; background: var(--primary-l); transition: width 1s linear; border-radius: 3px; }

        .btn-back {
            background: var(--surface-solid); color: var(--text); border: 1px solid var(--border); 
            font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; 
            margin-top: 10px; padding: 10px 20px; border-radius: 12px; box-shadow: var(--shadow-sm);
        }
        .btn-back:hover { background: var(--bg); color: var(--primary); border-color: var(--border-hover); }
        
        .footer { text-align: center; font-size: 0.8rem; color: var(--muted); padding: 24px 0 16px 0; z-index: 10; font-weight: 500; }

        /* ── Success Overlay ── */
        #success-overlay {
            display: none; position: fixed; inset: 0; z-index: 100;
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px);
            flex-direction: column; align-items: center; justify-content: center; gap: 20px; padding: 20px;
        }
        #success-overlay.show { display: flex; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .success-icon {
            width: 90px; height: 90px; border-radius: 50%;
            background: var(--secondary); color: #fff; font-size: 2.5rem;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }

        /* ── Mobile Responsiveness ── */
        @media (max-width: 900px) {
            .welcome-hero { flex-direction: column; }
            .about-card, .welcome-card { max-width: 100%; flex: auto; }
            .main { flex-direction: column; gap: 32px; }
            .welcome-main { gap: 48px; padding: 24px 0 40px 0; }
        }

        @media (max-width: 600px) {
            .kiosk, #welcome-screen { padding: 20px 16px; }
            .header { flex-direction: column; align-items: flex-start; gap: 12px; }
            #clock { width: 100%; text-align: center; }
            .glass-card, .step-card { padding: 24px; border-radius: 20px; }
            .login-grid, .steps-grid, .features-grid { grid-template-columns: 1fr; }
            .cam-frame { border-radius: 20px; }
            .cam-frame::after { inset: 10px; border-radius: 12px; }
            .privacy-banner { flex-direction: column; text-align: center; padding: 24px; }
        }
    </style>
</head>
<body>
    <div class="bg-scene"></div>
    <div class="bg-grid"></div>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    
    <svg class="ekg-container" viewBox="0 0 1000 100" preserveAspectRatio="none">
        <path class="ekg-line" d="M0,50 L350,50 L370,20 L390,80 L410,50 L600,50 L620,10 L640,90 L660,50 L1000,50"></path>
    </svg>    
    <!-- Medical EKG Animation -->
    <svg class="ekg-container" viewBox="0 0 1000 100" preserveAspectRatio="none">
        <path class="ekg-line" d="M0,50 L350,50 L370,20 L390,80 L410,50 L600,50 L620,10 L640,90 L660,50 L1000,50"></path>
    </svg>

    <!-- Timeout toast -->
    <div id="verify-toast" style="display:none;">
        <span id="toast-msg">Face not recognised. Please contact staff.</span>
    </div>

    <!-- Success overlay -->
    <div id="success-overlay">
        <div class="glass-card" style="align-items: center; text-align: center; max-width: 400px; width: 100%;">
            <div class="success-icon">✓</div>
            <div class="section-headline" id="success-name" style="margin-top:24px;">Face Recognized</div>
            <div class="section-desc" style="margin-bottom:0;">Retrieving medical profile securely...</div>
        </div>
    </div>

    <!-- ── Welcome Screen ── -->
    <div id="welcome-screen">
        <header class="header">
            <div class="logo">
                <div class="logo-icon">💊</div>
                <div>
                    <div class="logo-text">PharmaTrack</div>
                    <div class="logo-sub">Smart Pharmacy System</div>
                </div>
            </div>
            <div id="clock">--:--:--</div>
        </header>

        <div class="welcome-main">
            
            <div class="welcome-hero">
                <!-- About Section -->
                <div class="glass-card about-card">
                    <span class="badge">Overview</span>
                    <div class="section-headline">Clinical Decision <span>Support System</span></div>
                    <div class="section-desc">
                        PharmaTrack is an AI-enabled CDSS developed to help community pharmacists operate more efficiently. It seamlessly bridges advanced biometric technology with comprehensive patient care.
                    </div>
                </div>

                <!-- Interaction Section -->
                <div class="glass-card welcome-card">
                    <div class="section-headline">Secure Identity <span>Verification</span></div>
                    <div class="section-desc">
                        Walk up and scan your face to check in instantly. Biometric login ensures your personal health data is processed safely and exclusively for rapid identity verification.
                    </div>

                    <button class="btn-start" id="btn-start-verification">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        Start Face Verification
                    </button>

                    <div class="login-grid">
                        <a href="{{ route('login') }}" class="btn-staff">Patient Login</a>
                        <a href="{{ route('login') }}" class="btn-staff">Staff Login</a>
                    </div>
                </div>
            </div>

            <!-- "How it Works" Steps Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2>How to use <span>PharmaTrack</span></h2>
                    <p style="color: var(--muted); font-size: 0.95rem; margin-top: 8px;">For registered patients using the kiosk counter.</p>
                </div>
                
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-title">Position Your Face</div>
                        <div class="step-desc">Simply stand in front of the kiosk and look directly into the camera frame.</div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-title">AI Verification</div>
                        <div class="step-desc">Our secure facial recognition model instantly matches your biometric profile.</div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-title">Ready for Pharmacist</div>
                        <div class="step-desc">Your health and medication records are automatically routed to the pharmacist.</div>
                    </div>
                </div>

                <div class="registration-banner">
                    <div class="registration-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <line x1="19" y1="8" x2="19" y2="14"></line>
                            <line x1="22" y1="11" x2="16" y2="11"></line>
                        </svg>
                    </div>
                    <div class="registration-content">
                        <h3>Not registered for facial recognition yet?</h3>
                        <p>Please proceed to the pharmacy counter. Once our pharmacist registers your profile, you can use this check-in kiosk or log in to the portal instantly.</p>
                    </div>
                </div>
            </div>

            <!-- "Why PharmaTrack?" Visual Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Why choose <span>PharmaTrack?</span></h2>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a8 8 0 0 0-8 8c0 1.5.5 2.9 1.4 4l1.6 2v3a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-3l1.6-2A8.04 8.04 0 0 0 20 10a8 8 0 0 0-8-8z"></path>
                                <path d="M9 18h6"></path><path d="M10 22h4"></path>
                            </svg>
                        </div>
                        <div class="feature-title">AI-Enabled CDSS</div>
                        <div class="feature-desc">A smart Clinical Decision Support System to actively assist community pharmacists in delivering precision care.</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <path d="M9 3v18"></path><path d="M14 9h4"></path><path d="M14 15h4"></path>
                            </svg>
                        </div>
                        <div class="feature-title">Efficient Records</div>
                        <div class="feature-desc">Manage and monitor patient health and medication records faster and more accurately through a unified dashboard.</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                        <div class="feature-title">Frictionless Check-in</div>
                        <div class="feature-desc">Leverage advanced facial recognition models for instant, secure, and touchless patient authentication.</div>
                    </div>
                </div>
            </div>

            <!-- Privacy & Security Assurance Banner -->
            <div class="privacy-banner">
                <div class="privacy-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <path d="M9 12l2 2 4-4"></path>
                    </svg>
                </div>
                <div class="privacy-content">
                    <h3>Your Data is Secure</h3>
                    <p>PharmaTrack operates under strict confidentiality protocols. Your biometric data and medical records are heavily encrypted and never shared with third parties. Facial scans are processed locally solely for identity verification.</p>
                </div>
            </div>

        </div>

        <footer class="footer">&copy; {{ date('Y') }} PharmaTrack &mdash; Smart Pharmacy System.</footer>
    </div>

    <!-- ── Kiosk Scanning Screen ── -->
    <div class="kiosk">
        <header class="header">
            <div class="logo">
                <div class="logo-icon">💊</div>
                <div>
                    <div class="logo-text">PharmaTrack</div>
                    <div class="logo-sub">Smart Pharmacy System</div>
                </div>
            </div>
        </header>

        <main class="main">
            <!-- Left Info Panel on Scanning Screen -->
            <div class="glass-card" style="max-width: 440px; width: 100%;">
                <div class="section-headline" style="font-size: clamp(2rem, 5vw, 2.5rem);">Ready to verify your <span>identity</span></div>
                
                <div class="steps-grid" style="display:flex; flex-direction:column; gap:12px; margin-top:24px;">
                    <div style="display:flex; align-items:center; gap:16px; padding:16px; border-radius:16px; background:var(--surface-solid); border:1px solid var(--border); box-shadow:var(--shadow-sm);">
                        <div style="width:36px; height:36px; border-radius:12px; flex-shrink:0; background:rgba(14,165,233,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800;">1</div>
                        <div style="font-size:0.95rem; font-weight:500; color:var(--text);">Face the camera directly</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px; padding:16px; border-radius:16px; background:var(--surface-solid); border:1px solid var(--border); box-shadow:var(--shadow-sm);">
                        <div style="width:36px; height:36px; border-radius:12px; flex-shrink:0; background:rgba(14,165,233,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800;">2</div>
                        <div style="font-size:0.95rem; font-weight:500; color:var(--text);">Wait for AI verification</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px; padding:16px; border-radius:16px; background:var(--surface-solid); border:1px solid var(--border); box-shadow:var(--shadow-sm);">
                        <div style="width:36px; height:36px; border-radius:12px; flex-shrink:0; background:rgba(14,165,233,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800;">3</div>
                        <div style="font-size:0.95rem; font-weight:500; color:var(--text);">Records retrieved instantly</div>
                    </div>
                </div>
            </div>

            <div class="cam-panel">
                <div class="cam-frame" id="cam-frame">
                    <div class="scan-line" id="scan-line"></div>
                    <video id="video" autoplay muted playsinline></video>
                    <canvas id="overlay-canvas"></canvas>
                </div>
                
                <div class="status-badge" id="status-badge">
                    <div class="status-dot" id="status-dot"></div>
                    <span id="status-text">Initializing AI Engine…</span>
                </div>

                <select id="camera-select" style="display:none;"></select>

                <div class="timeout-wrap">
                    <span id="timeout-label">30s</span>
                    <div id="timeout-bar"><div id="timeout-fill"></div></div>
                </div>

                <button class="btn-back" id="btn-back">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                    Cancel & Return
                </button>
            </div>
        </main>
        <footer class="footer">Facial recognition data is used strictly for localized patient identification.</footer>
    </div>

    <!-- Script dependencies exactly as original -->
    <script src="{{ asset('js/face-api.min.js') }}"></script>
    <script>
    const clockEl = document.getElementById('clock');
    const tick = () => {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-MY', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    };
    tick(); setInterval(tick, 1000);

    document.getElementById('btn-start-verification').addEventListener('click', async () => {
        document.getElementById('welcome-screen').style.display = 'none';
        document.querySelector('.kiosk').style.display = 'flex';
        await startVerification();
    });

    function cancelVerification(toastMsg = null) {
        if (window._kioskStream) {
            window._kioskStream.getTracks().forEach(t => t.stop());
            window._kioskStream = null;
        }
        if (window._scanInterval)  { clearInterval(window._scanInterval);  window._scanInterval  = null; }
        if (window._verifyTimeout) { clearTimeout(window._verifyTimeout);  window._verifyTimeout = null; }
        if (window._countdownInt)  { clearInterval(window._countdownInt);  window._countdownInt  = null; }

        const fill = document.getElementById('timeout-fill');
        if (fill) fill.style.width = '100%';

        document.querySelector('.kiosk').style.display = 'none';
        document.getElementById('welcome-screen').style.display = 'flex';

        if (toastMsg) { alert(toastMsg); /* Fallback or replace with toast element logic */ }
    }

    document.getElementById('btn-back').addEventListener('click', () => { cancelVerification(); });

    async function startVerification() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('overlay-canvas');
        const statusBadge = document.getElementById('status-badge');
        const statusText = document.getElementById('status-text');
        const statusDot = document.getElementById('status-dot');
        const scanLine = document.getElementById('scan-line');
        const successOv = document.getElementById('success-overlay');
        const successName = document.getElementById('success-name');
        
        const THRESHOLD = 0.45;
        let faceMatcher = null;
        let redirecting = false;

        let registeredPatients = [];
        try {
            const res = await fetch('{{ route("kiosk.patients") }}');
            registeredPatients = await res.json();
        } catch (e) {
            setStatus('Could not load patient data.', 'error');
        }

        setStatus('Loading AI models…', 'info');
        try {
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri('{{ asset("models") }}'),
                faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset("models") }}'),
                faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset("models") }}'),
            ]);
        } catch (e) {
            setStatus('AI model files not found.', 'error');
            return;
        }

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

        startCamera();

        const TIMEOUT_SEC = 30;
        let remaining = TIMEOUT_SEC;
        const fill = document.getElementById('timeout-fill');
        const timeoutLabel = document.getElementById('timeout-label');
        fill.style.width = '100%';
        timeoutLabel.textContent = `${remaining}s`;

        window._countdownInt = setInterval(() => {
            remaining--;
            timeoutLabel.textContent = `${remaining}s`;
            fill.style.width = `${(remaining / TIMEOUT_SEC) * 100}%`;
            if (remaining <= 0) { clearInterval(window._countdownInt); }
        }, 1000);

        window._verifyTimeout = setTimeout(() => {
            if (!redirecting) { cancelVerification('Timeout: Face not recognised.'); }
        }, TIMEOUT_SEC * 1000);

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                window._kioskStream = stream;
                video.srcObject = stream;
                setStatus('System live — scanning for faces…', 'info');
            } catch (e) {
                setStatus('Could not start camera.', 'error');
            }
        }

        video.addEventListener('play', () => {
            const displaySize = { width: video.clientWidth, height: video.clientHeight };
            canvas.width = displaySize.width;
            canvas.height = displaySize.height;
            faceapi.matchDimensions(canvas, displaySize);

            let busy = false;
            window._scanInterval = setInterval(async () => {
                if (busy || !faceMatcher || redirecting) return;
                busy = true;

                const currentSize = { width: video.clientWidth, height: video.clientHeight };
                if(canvas.width !== currentSize.width) {
                    canvas.width = currentSize.width;
                    canvas.height = currentSize.height;
                    faceapi.matchDimensions(canvas, currentSize);
                }

                const detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                if (!detection) {
                    setStatus('No face detected — please look at the camera.', 'info');
                    busy = false; return;
                }

                const resized = faceapi.resizeResults(detection, currentSize);
                const box = resized.detection.box;
                ctx.strokeStyle = '#0ea5e9';
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);

                const match = faceMatcher.findBestMatch(detection.descriptor);

                if (match.label !== 'unknown' && match.distance <= THRESHOLD) {
                    redirecting = true;
                    clearInterval(window._scanInterval);
                    clearTimeout(window._verifyTimeout);
                    clearInterval(window._countdownInt);
                    setStatus('Patient identified! Redirecting…', 'success');
                    scanLine.style.display = 'none';

                    const p = registeredPatients.find(x => x.id.toString() === match.label);
                    successName.textContent = p ? `Welcome, ${p.name}!` : 'Face Recognized';
                    successOv.classList.add('show');

                    setTimeout(async () => {
                        try {
                            const res = await fetch(`/kiosk/auth/${match.label}`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
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

        function setStatus(msg, state) {
            statusText.textContent = msg;
            statusBadge.className = `status-badge ${state}`;
        }
    }
    </script>
</body>
</html>