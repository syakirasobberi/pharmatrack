<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaTrack — Smart Check-In</title>
    <meta name="description" content="PharmaTrack facial recognition kiosk. Walk up and scan your face to check in instantly.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:    #0d9488; /* Brand Teal */
            --primary-l:  #14b8a6; /* Light Teal */
            --secondary:  #059669; /* Success Green */
            --bg:         #f8fafc; /* Crisp Light Gray/Blue */
            --surface:    rgba(255, 255, 255, 0.75);
            --surface-solid: #ffffff;
            --border:     rgba(20, 184, 166, 0.15);
            --border-hover: rgba(20, 184, 166, 0.3);
            --text:       #0f172a; /* Slate Dark */
            --muted:      #64748b; /* Slate Gray */
            --shadow-sm:  0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg:  0 12px 32px rgba(20, 184, 166, 0.08);
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
            background-image: radial-gradient(rgba(20, 184, 166, 0.15) 1px, transparent 1px);
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
            background: rgba(20, 184, 166, 0.4); /* Primary blue */
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

        /* While the camera scan runs, drop the animated/blurred decoration so face detection gets the CPU. */
        body.scanning .bg-blob,
        body.scanning .ekg-container { display: none; }
        body.scanning .glass-card { backdrop-filter: none; animation: none; }

        @media (prefers-reduced-motion: reduce) {
            .bg-blob, .ekg-line, .glass-card, .content-section { animation: none; }
            .ekg-line { stroke-dashoffset: 0; }
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
            background: rgba(13, 148, 136, 0.1);
            display: flex; align-items: center; justify-content: center;
            color: #0d9488; border: 1px solid rgba(13, 148, 136, 0.18);
        }
        .logo-icon svg { width: 30px; height: 30px; }
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
            position: relative; overflow: hidden;
            display: flex; align-items: stretch; justify-content: center; gap: 32px;
            width: 100%; max-width: 1100px; min-height: 430px;
            padding: 34px;
            border: 1px solid rgba(255, 255, 255, 0.72); border-radius: 30px;
            background-image:
                linear-gradient(90deg, rgba(248, 250, 252, 0.96), rgba(240, 253, 250, 0.82) 46%, rgba(20, 184, 166, 0.16)),
                url('{{ asset('storage/bg3.png') }}');
            background-size: cover;
            background-position: center;
            box-shadow: 0 22px 54px rgba(20, 184, 166, 0.16);
        }

        .glass-card {
            min-width: 0;
            background: var(--surface); backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border); border-radius: 28px;
            padding: 40px; box-shadow: var(--shadow-lg);
            display: flex; flex-direction: column;
            animation: floatUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .welcome-card { flex: 1.2; background: rgba(255, 255, 255, 0.93); min-width: 280px; }
        .about-card { 
            flex: 1; min-width: 280px;
            background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(240, 253, 250,0.82));
        }

        @keyframes floatUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .section-headline { font-size: clamp(1.6rem, 4vw, 2rem); font-weight: 800; line-height: 1.2; margin-bottom: 16px; color: var(--text); }
        .section-headline span { color: var(--primary); }
        .section-desc { font-size: 0.95rem; color: var(--muted); line-height: 1.6; margin-bottom: 24px; }

        .badge {
            display: inline-block; padding: 6px 14px; border-radius: 20px;
            background: rgba(20, 184, 166, 0.1); color: var(--primary);
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 16px;
            align-self: flex-start;
        }

        .btn-start {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            width: 100%; padding: 16px 24px; border-radius: 16px;
            font-size: 1.05rem; font-weight: 700; cursor: pointer; border: none;
            background: linear-gradient(135deg, var(--primary-l), var(--primary)); color: #fff;
            box-shadow: 0 8px 24px rgba(20, 184, 166, 0.25); transition: all 0.3s ease;
        }
        .btn-start:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(20, 184, 166, 0.35); }
        
        .login-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-top: 16px; }
        
        .btn-staff {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px; border-radius: 14px; font-size: 0.9rem; font-weight: 600;
            border: 1px solid var(--border); background: var(--surface-solid);
            color: var(--text); text-decoration: none; transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }
        .btn-staff:hover { background: var(--bg); border-color: var(--border-hover); color: var(--primary); }

        /* Visible keyboard focus for every interactive element */
        a:focus-visible, button:focus-visible {
            outline: 3px solid var(--primary-l); outline-offset: 3px;
        }
        .btn-start:focus-visible, .btn-login-instead:focus-visible {
            outline-color: var(--text);
        }

        /* ── Registration Banner ── */
        .registration-banner {
            width: 100%; max-width: 1100px;
            background: #f0fdfa; border: 1px solid #99f6e4;
            border-radius: 20px; padding: 24px 32px;
            display: flex; align-items: center; gap: 20px;
            box-shadow: var(--shadow-sm); margin-top: 8px;
        }

        .registration-icon {
            flex-shrink: 0; width: 48px; height: 48px; border-radius: 14px;
            background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.2);
        }

        .registration-content h3 { font-size: 1.05rem; font-weight: 700; color: #0f766e; margin-bottom: 4px; }
        .registration-content p { font-size: 0.9rem; color: #115e59; line-height: 1.5; }

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
            display: grid; grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr));
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
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
        }

        .step-title { font-size: 1.15rem; font-weight: 700; color: var(--text); margin-bottom: 12px; }
        .step-desc { font-size: 0.9rem; color: var(--muted); line-height: 1.5; }

        /* ── "Why PharmaTrack?" Visual Section ── */
        .features-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));
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
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.1), rgba(13, 148, 136, 0.15));
            display: flex; align-items: center; justify-content: center;
            color: var(--primary); border: 1px solid rgba(20, 184, 166, 0.2);
        }

        .feature-title { font-size: 1.15rem; font-weight: 700; color: var(--text); margin-bottom: 12px; }
        .feature-desc { font-size: 0.9rem; color: var(--muted); line-height: 1.5; }

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

        /* ── Top navigation ── */
        html { scroll-behavior: smooth; }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .top-nav { display: flex; align-items: center; gap: 4px; }
        .top-nav a {
            padding: 10px 14px; border-radius: 10px; font-size: 0.88rem; font-weight: 600;
            color: var(--muted); text-decoration: none; transition: color .2s, background .2s;
        }
        .top-nav a:hover { color: var(--primary); background: rgba(20, 184, 166, 0.08); }
        .top-nav a.nav-login {
            color: #fff; background: var(--primary); margin-left: 6px;
        }
        .top-nav a.nav-login:hover { background: #0f766e; color: #fff; }
        [id] { scroll-margin-top: 24px; }

        /* ── EN / BM language toggle ── */
        .lang-toggle {
            display: inline-flex; padding: 4px; gap: 2px; flex-shrink: 0;
            background: var(--surface-solid); border: 1px solid var(--border);
            border-radius: 12px; box-shadow: var(--shadow-sm);
        }
        .lang-toggle a {
            min-width: 42px; padding: 7px 10px; border-radius: 9px; text-align: center;
            font-size: 0.82rem; font-weight: 800; letter-spacing: 0.04em;
            color: var(--muted); text-decoration: none; transition: background .2s, color .2s;
        }
        .lang-toggle a:hover { color: var(--primary); }
        .lang-toggle a.active { background: var(--primary); color: #fff; }

        /* ── Hero extras ── */
        .trust-chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 20px; list-style: none; }
        .trust-chips li {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 600;
            background: #f0fdfa; color: #0f766e; border: 1px solid #ccfbf1;
        }
        .trust-chips svg { flex-shrink: 0; color: var(--primary); }
        .hero-links { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-top: 12px; font-size: 0.85rem; }
        .hero-links a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .hero-links a:hover { text-decoration: underline; }
        .fact-list { list-style: none; display: flex; flex-direction: column; gap: 14px; margin-top: 4px; }
        .fact-list li { display: flex; gap: 12px; align-items: flex-start; font-size: 0.92rem; color: var(--text); line-height: 1.5; }
        .fact-list .fact-icon {
            flex-shrink: 0; width: 32px; height: 32px; border-radius: 10px;
            background: rgba(20, 184, 166, 0.1); color: var(--primary);
            display: flex; align-items: center; justify-content: center;
        }
        .fact-list strong { display: block; font-size: 0.95rem; }
        .fact-list span.fact-sub { color: var(--muted); font-size: 0.85rem; }

        /* ── Patient portal list ── */
        .portal-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));
            gap: 16px; width: 100%;
        }
        .portal-item {
            display: flex; gap: 14px; align-items: flex-start;
            background: var(--surface-solid); border: 1px solid var(--border);
            border-radius: 18px; padding: 20px; box-shadow: var(--shadow-sm);
        }
        .portal-item .fact-icon {
            flex-shrink: 0; width: 40px; height: 40px; border-radius: 12px;
            background: rgba(20, 184, 166, 0.1); color: var(--primary);
            display: flex; align-items: center; justify-content: center;
        }
        .portal-item h3 { font-size: 1rem; font-weight: 700; margin-bottom: 4px; }
        .portal-item p { font-size: 0.86rem; color: var(--muted); line-height: 1.5; }
        .section-cta { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
        .section-cta a {
            padding: 12px 22px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; text-decoration: none;
            background: var(--primary); color: #fff; box-shadow: 0 6px 18px rgba(20, 184, 166, 0.25);
        }
        .section-cta a.secondary { background: var(--surface-solid); color: var(--text); border: 1px solid var(--border); box-shadow: var(--shadow-sm); }

        /* ── FAQ ── */
        .faq-list { width: 100%; max-width: 820px; display: flex; flex-direction: column; gap: 12px; }
        .faq-list details {
            background: var(--surface-solid); border: 1px solid var(--border);
            border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden;
        }
        .faq-list summary {
            cursor: pointer; list-style: none; padding: 18px 22px;
            font-weight: 700; font-size: 0.98rem; display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        .faq-list summary::-webkit-details-marker { display: none; }
        .faq-list summary::after {
            content: '+'; font-size: 1.4rem; font-weight: 600; color: var(--primary); line-height: 1; flex-shrink: 0;
        }
        .faq-list details[open] summary::after { content: '−'; }
        .faq-list details[open] summary { color: var(--primary); }
        .faq-list .faq-body { padding: 0 22px 18px; color: var(--muted); font-size: 0.92rem; line-height: 1.6; }
        .faq-list .faq-body a { color: var(--primary); font-weight: 600; }

        /* ── Visit / contact ── */
        .visit-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr));
            gap: 16px; width: 100%;
        }
        .visit-card {
            background: var(--surface-solid); border: 1px solid var(--border); border-radius: 20px;
            padding: 24px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 8px;
        }
        .visit-card h3 { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--primary); font-weight: 800; }
        .visit-card p { font-size: 0.95rem; line-height: 1.6; color: var(--text); }
        .visit-card a { color: var(--primary); font-weight: 700; text-decoration: none; }
        .visit-card a:hover { text-decoration: underline; }
        .disclaimer {
            width: 100%; max-width: 1100px; display: flex; gap: 16px; align-items: flex-start;
            background: #fffbeb; border: 1px solid #fde68a; border-radius: 20px; padding: 20px 24px;
            color: #92400e; font-size: 0.88rem; line-height: 1.6;
        }
        .disclaimer strong { color: #78350f; }
        .disclaimer svg { flex-shrink: 0; margin-top: 2px; }

        @media (max-width: 1000px) {
            .top-nav a:not(.nav-login) { display: none; }
        }

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
            border: 2px solid rgba(20, 184, 166, 0.5); border-radius: 16px; pointer-events: none;
        }

        .scan-line {
            position: absolute; left: 0; right: 0; height: 3px; z-index: 9;
            background: linear-gradient(90deg, transparent, var(--primary-l), transparent);
            box-shadow: 0 0 16px var(--primary-l); animation: scanSweep 3s ease-in-out infinite;
        }
        @keyframes scanSweep { 0% { top: 10%; opacity: 0; } 10% { opacity: 1; } 50% { top: 90%; } 90% { opacity: 1; } 100% { top: 10%; opacity: 0; } }

        /* Mirrored like a mirror, so "turn to your left" moves the face to the left on screen. */
        #video, #overlay-canvas { transform: scaleX(-1); }
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

        /* Kiosk instruction list */
        .kiosk-steps { display: flex; flex-direction: column; gap: 12px; margin-top: 24px; list-style: none; }
        .kiosk-step {
            display: flex; align-items: center; gap: 16px; padding: 16px; border-radius: 16px;
            background: var(--surface-solid); border: 1px solid var(--border); box-shadow: var(--shadow-sm);
            font-size: 0.95rem; font-weight: 500; color: var(--text);
        }
        .kiosk-step-num {
            width: 36px; height: 36px; border-radius: 12px; flex-shrink: 0;
            background: rgba(20, 184, 166, 0.1); color: var(--primary);
            display: flex; align-items: center; justify-content: center; font-weight: 800;
        }

        .btn-retry {
            display: none; padding: 10px 20px; border-radius: 12px; border: none; cursor: pointer;
            background: var(--primary); color: #fff; font-size: 0.9rem; font-weight: 600;
        }
        .btn-retry.show { display: inline-flex; }

        /* ── Timeout Bar ── */
        .timeout-wrap { width: 100%; max-width: 300px; display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--muted); font-weight: 600; }
        #timeout-bar { flex: 1; height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
        #timeout-fill { height: 100%; background: var(--primary-l); transition: width 1s linear; border-radius: 3px; }

        .scan-actions {
            display: flex; align-items: center; justify-content: center; flex-wrap: wrap;
            gap: 12px; margin-top: 10px;
        }

        .btn-back, .btn-login-instead {
            background: var(--surface-solid); color: var(--text); border: 1px solid var(--border); 
            font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; 
            padding: 10px 20px; border-radius: 12px; box-shadow: var(--shadow-sm);
            text-decoration: none; transition: all 0.2s;
        }
        .btn-back:hover { background: var(--bg); color: var(--primary); border-color: var(--border-hover); }
        .btn-login-instead {
            background: var(--primary); color: #fff; border-color: var(--primary);
        }
        .btn-login-instead:hover {
            background: #0d9488; border-color: #0d9488; transform: translateY(-1px);
        }
        

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
            .welcome-hero {
                flex-direction: column;
                min-height: auto;
                padding: 24px;
                background-position: center right;
            }
            .about-card, .welcome-card { max-width: 100%; flex: auto; }
            .main { flex-direction: column; gap: 32px; }
            .welcome-main { gap: 48px; padding: 24px 0 40px 0; }
        }

        @media (max-width: 600px) {
            .kiosk, #welcome-screen { padding: 20px 16px; }
            .header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .header-right { width: 100%; }
            #clock { flex: 1; text-align: center; }
            .disclaimer { flex-direction: column; }
            .welcome-main { gap: 36px; }
            .welcome-hero { gap: 18px; padding: 16px; border-radius: 22px; background-position: center; }
            .content-section { gap: 24px; }
            .section-header h2 { font-size: 1.45rem; }
            .glass-card, .step-card { padding: 24px; border-radius: 20px; }
            .login-grid, .steps-grid, .features-grid { grid-template-columns: 1fr; }
            .cam-frame { border-radius: 20px; }
            .cam-frame::after { inset: 10px; border-radius: 12px; }
            .privacy-banner { flex-direction: column; text-align: center; padding: 24px; }
        }

        @media (max-width: 380px) {
            .kiosk, #welcome-screen { padding: 16px 10px; }
            .welcome-hero { padding: 12px; border-radius: 18px; }
            .glass-card, .step-card, .registration-banner, .privacy-banner { padding: 18px; }
            .btn-start { padding: 14px 16px; font-size: 0.95rem; }
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
</head>
<body>
    <div class="bg-scene"></div>
    <div class="bg-grid"></div>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    
    <!-- Medical EKG Animation -->
    <svg class="ekg-container" viewBox="0 0 1000 100" preserveAspectRatio="none">
        <path class="ekg-line" d="M0,50 L350,50 L370,20 L390,80 L410,50 L600,50 L620,10 L640,90 L660,50 L1000,50"></path>
    </svg>

    <!-- Timeout toast -->
    <div id="verify-toast" role="status" aria-live="polite"
         style="display:none; position:fixed; left:50%; bottom:32px; transform:translateX(-50%); z-index:1000; max-width:90vw;
                padding:14px 22px; border-radius:14px; background:#7f1d1d; color:#fff; font-weight:600; font-size:15px;
                box-shadow:0 10px 30px rgba(0,0,0,.25);">
        <span id="toast-msg">{{ __('Face not recognised. Please contact staff.') }}</span>
    </div>

    <!-- Success overlay -->
    <div id="success-overlay">
        <div class="glass-card" style="align-items: center; text-align: center; max-width: 400px; width: 100%;">
            <div class="success-icon">✓</div>
            <div class="section-headline" id="success-name" style="margin-top:24px;">{{ __('Face Recognized') }}</div>
            <div class="section-desc" style="margin-bottom:0;">{{ __('Retrieving medical profile securely...') }}</div>
        </div>
    </div>

    <!-- ── Welcome Screen ── -->
    <div id="welcome-screen">
        <header class="header">
            <div class="logo">
                <x-brand :size="48" :tagline="__('Smart Pharmacy System')" />
            </div>
            <div class="header-right">
                <nav class="top-nav" aria-label="Page sections">
                    <a href="#how-it-works">{{ __('How it works') }}</a>
                    <a href="#patient-portal">{{ __('Patient portal') }}</a>
                    <a href="#faq">{{ __('FAQ') }}</a>
                    <a href="#visit">{{ __('Contact') }}</a>
                    <a href="{{ route('login') }}" class="nav-login">{{ __('Log in') }}</a>
                </nav>
                <div class="lang-toggle" role="group" aria-label="{{ __('Language') }}">
                    <a href="{{ route('locale.switch', 'en') }}" lang="en" hreflang="en" @class(['active' => app()->getLocale() !== 'ms']) @if (app()->getLocale() !== 'ms') aria-current="true" @endif>EN</a>
                    <a href="{{ route('locale.switch', 'ms') }}" lang="ms" hreflang="ms" @class(['active' => app()->getLocale() === 'ms']) @if (app()->getLocale() === 'ms') aria-current="true" @endif>BM</a>
                </div>
                <div id="clock">--:--:--</div>
            </div>
        </header>

        @php
            $pharmacy = config('services.pharmacy');
            $waNumber = preg_replace('/\D+/', '', $pharmacy['whatsapp'] ?? '');
            $waLink   = 'https://wa.me/' . $waNumber;
            $waPretty = '+' . $waNumber;
        @endphp

        <main class="welcome-main">

            <div class="welcome-hero">
                <!-- Check-in (primary action, shown first) -->
                <section class="glass-card welcome-card" aria-labelledby="hero-title">
                    <span class="badge">{{ __('Patient check-in') }}</span>
                    <h1 class="section-headline" id="hero-title">{!! __('Check in with <span>your face</span>') !!}</h1>
                    <p class="section-desc">
                        {{ __('Look at the camera, turn your head when asked, and your pharmacist will have your records ready. No card or password needed.') }}
                    </p>

                    <ul class="trust-chips" aria-label="Check-in at a glance">
                        <li>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2.5"/><path d="M9.5 2h5"/></svg>
                            {{ __('About 10 seconds') }}
                        </li>
                        <li>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M13 10h5M13 14h3"/><path d="M3 3l18 18"/></svg>
                            {{ __('No card or IC needed') }}
                        </li>
                        <li>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                            {{ __('No photo saved') }}
                        </li>
                    </ul>

                    <button type="button" class="btn-start" id="btn-start-verification">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        {{ __('Start Face Verification') }}
                    </button>

                    <div class="login-grid">
                        <a href="{{ route('login') }}" class="btn-staff">{!! __('Log in with email &amp; password') !!}</a>
                    </div>
                    <div class="hero-links">
                        <a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                        <a href="#faq">{{ __('Need help?') }}</a>
                    </div>
                </section>

                <!-- About + who it is for -->
                <section class="glass-card about-card" aria-labelledby="about-title">
                    <span class="badge">{{ __('About PharmaTrack') }}</span>
                    <h2 class="section-headline" id="about-title">{!! __('Smarter care at your <span>community pharmacy</span>') !!}</h2>
                    <p class="section-desc">
                        {{ __('PharmaTrack keeps your medications and health check-ups in one place, and uses AI-assisted clinical decision support to help your pharmacist spot possible health risks early.') }}
                    </p>
                    <ul class="fact-list">
                        <li>
                            <span class="fact-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                            <span><strong>{{ __('Registered patients') }}</strong><span class="fact-sub">{{ __('Check in at the kiosk, or log in to see your medicines and results.') }}</span></span>
                        </li>
                        <li>
                            <span class="fact-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></span>
                            <span><strong>{{ __('New patients') }}</strong><span class="fact-sub">{{ __('Visit the counter. Our pharmacist registers you; it only takes a few minutes.') }}</span></span>
                        </li>
                        <li>
                            <span class="fact-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                            <span><strong>{{ __('Pharmacy staff') }}</strong><span class="fact-sub">{{ __('Use “Log in”. Staff sign-in is protected with an emailed code.') }}</span></span>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- "How it Works" Steps Section -->
            <div class="content-section" id="how-it-works">
                <div class="section-header">
                    <h2>{!! __('How to use <span>PharmaTrack</span>') !!}</h2>
                    <p style="color: var(--muted); font-size: 0.95rem; margin-top: 8px;">{{ __('For registered patients using the kiosk counter.') }}</p>
                </div>
                
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3 class="step-title">{{ __('Position Your Face') }}</h3>
                        <p class="step-desc">{{ __('Simply stand in front of the kiosk and look directly into the camera frame.') }}</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3 class="step-title">{{ __('Turn Your Head') }}</h3>
                        <p class="step-desc">{{ __('Turn your head to the side shown on screen until you hear a beep, then look back at the camera. This proves a real person is in front of the camera.') }}</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3 class="step-title">{{ __('AI Verification') }}</h3>
                        <p class="step-desc">{{ __('Hold still while our facial recognition model matches your profile.') }}</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h3 class="step-title">{{ __('Ready for Pharmacist') }}</h3>
                        <p class="step-desc">{{ __('Your health and medication records are automatically routed to the pharmacist.') }}</p>
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
                        <h3>{{ __('Not registered for face check-in yet?') }}</h3>
                        <p>{{ __('Please proceed to the pharmacy counter. Once our pharmacist registers your profile, you can use this check-in kiosk or log in to the portal instantly.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Patient portal -->
            <div class="content-section" id="patient-portal">
                <div class="section-header">
                    <h2>{!! __('Your <span>patient portal</span>') !!}</h2>
                    <p style="color: var(--muted); font-size: 0.95rem; margin-top: 8px;">{{ __('Log in from any phone or computer to manage your care between visits.') }}</p>
                </div>

                <div class="portal-grid">
                    <div class="portal-item">
                        <span class="fact-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 20.5 3.5 13.5a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7z"/><path d="m8.5 8.5 7 7"/></svg></span>
                        <div><h3>{{ __('My medicines') }}</h3><p>{{ __('See every medicine, its dose and instructions, all in one list.') }}</p></div>
                    </div>
                    <div class="portal-item">
                        <span class="fact-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                        <div><h3>{{ __('Dose log') }}</h3><p>{{ __('Tick off each dose you take and track how well you are keeping up.') }}</p></div>
                    </div>
                    <div class="portal-item">
                        <span class="fact-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"/><path d="M20.5 15a9 9 0 1 1-2.1-9.4L23 10"/></svg></span>
                        <div><h3>{!! __('Refill requests &amp; reminders') !!}</h3><p>{{ __('Ask for a refill online and get reminded before you run out or a check-up is due.') }}</p></div>
                    </div>
                    <div class="portal-item">
                        <span class="fact-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></span>
                        <div><h3>{{ __('Check-up results') }}</h3><p>{{ __('Blood pressure, sugar, cholesterol and BMI, with clear Normal / High / Low labels.') }}</p></div>
                    </div>
                    <div class="portal-item">
                        <span class="fact-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg></span>
                        <div><h3>{{ __('Health summary') }}</h3><p>{{ __('Download or email yourself a summary to show your doctor or clinic.') }}</p></div>
                    </div>
                    <div class="portal-item">
                        <span class="fact-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        <div><h3>{{ __('Control your face data') }}</h3><p>{{ __('Remove your face template at any time and use email login instead.') }}</p></div>
                    </div>
                </div>

                <div class="section-cta">
                    <a href="{{ route('login') }}">{{ __('Log in to the portal') }}</a>
                    <a href="{{ route('password.request') }}" class="secondary">{{ __('Set or reset password') }}</a>
                </div>
            </div>

            <!-- "Why PharmaTrack?" Visual Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2>{!! __('What PharmaTrack <span>does for you</span>') !!}</h2>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a8 8 0 0 0-8 8c0 1.5.5 2.9 1.4 4l1.6 2v3a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-3l1.6-2A8.04 8.04 0 0 0 20 10a8 8 0 0 0-8-8z"></path>
                                <path d="M9 18h6"></path><path d="M10 22h4"></path>
                            </svg>
                        </div>
                        <h3 class="feature-title">{{ __('Early Health Warnings') }}</h3>
                        <p class="feature-desc">{{ __('AI-assisted clinical decision support helps your pharmacist spot possible health risks from your check-up results early.') }}</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <path d="M9 3v18"></path><path d="M14 9h4"></path><path d="M14 15h4"></path>
                            </svg>
                        </div>
                        <h3 class="feature-title">{{ __('All Your Records in One Place') }}</h3>
                        <p class="feature-desc">{{ __('Your medications and health check-ups are kept together, so your pharmacist sees the full picture every visit.') }}</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                        <h3 class="feature-title">{{ __('Touchless Check-in') }}</h3>
                        <p class="feature-desc">{{ __('No card, queue number or password: check in at the kiosk with your face in a few seconds.') }}</p>
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
                    <h3>{{ __('Your Data is Secure') }}</h3>
                    <p>{{ __('No photo of your face is saved. The camera image is turned into a numeric face template on this device, and only that template is sent to our server to match you against registered patients. It is used solely for identity verification. Your face is only enrolled with your consent (PDPA 2010), and you can remove it at any time from your patient portal or at the counter.') }}</p>
                </div>
            </div>

            <!-- FAQ -->
            <div class="content-section" id="faq">
                <div class="section-header">
                    <h2>{!! __('Frequently asked <span>questions</span>') !!}</h2>
                </div>

                <div class="faq-list">
                    <details>
                        <summary>{{ __('The kiosk does not recognise my face. What should I do?') }}</summary>
                        <div class="faq-body">{!! __('Remove glasses, mask or hat, face the camera in good light and turn your head when asked. If it still fails, tap <strong>Login Instead</strong> to sign in with your email, or ask the pharmacist at the counter, who can also update your face profile.') !!}</div>
                    </details>
                    <details>
                        <summary>{{ __('How do I get an account?') }}</summary>
                        <div class="faq-body">{!! __('Accounts are created by our pharmacist at the counter. You will receive an email with a link to set your own password. Didn\'t get it? Check your spam folder, or use <a href=":url">Forgot password</a> with the same email.', ['url' => e(route('password.request'))]) !!}</div>
                    </details>
                    <details>
                        <summary>{{ __('Do I have to use face check-in?') }}</summary>
                        <div class="faq-body">{{ __('No. Face check-in is optional and only set up with your consent. You can always log in with your email and password instead, and you can remove your face data at any time.') }}</div>
                    </details>
                    <details>
                        <summary>{{ __('Is my face photo stored?') }}</summary>
                        <div class="faq-body">{{ __('No. Only a numeric face template (a list of numbers that cannot be turned back into a photo) is kept, and it is used only to recognise you at this pharmacy.') }}</div>
                    </details>
                    <details>
                        <summary>{{ __('Is the AI health risk result a diagnosis?') }}</summary>
                        <div class="faq-body">{{ __('No. It is a decision-support tool that helps your pharmacist notice possible risks early. Any concern is discussed with you, and you may be referred to a doctor for proper diagnosis.') }}</div>
                    </details>
                    <details>
                        <summary>{{ __('I forgot my password.') }}</summary>
                        <div class="faq-body">{!! __('Use <a href=":url">Forgot password</a> and we will email you a reset link. If you no longer have access to that email, ask the pharmacist to update it.', ['url' => e(route('password.request'))]) !!}</div>
                    </details>
                </div>
            </div>

            <!-- Visit / contact -->
            <div class="content-section" id="visit">
                <div class="section-header">
                    <h2>{!! __('Visit or <span>contact us</span>') !!}</h2>
                </div>

                <div class="visit-grid">
                    @if (!empty($pharmacy['hours']))
                        <div class="visit-card">
                            <h3>{{ __('Opening hours') }}</h3>
                            <p>{!! collect(explode('|', $pharmacy['hours']))->map(fn ($l) => e(trim($l)))->implode('<br>') !!}</p>
                        </div>
                    @endif
                    @if (!empty($pharmacy['address']))
                        <div class="visit-card">
                            <h3>{{ __('Address') }}</h3>
                            <p>{{ $pharmacy['address'] }}</p>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($pharmacy['address']) }}" target="_blank" rel="noopener">{{ __('Get directions →') }}</a>
                        </div>
                    @endif
                    <div class="visit-card">
                        <h3>WhatsApp</h3>
                        <p>{{ __('Questions about your medicines, refills or account.') }}</p>
                        <a href="{{ $waLink }}" target="_blank" rel="noopener">{{ __('Chat on :number →', ['number' => $waPretty]) }}</a>
                    </div>
                    @if (!empty($pharmacy['phone']))
                        <div class="visit-card">
                            <h3>{{ __('Phone') }}</h3>
                            <p>{{ __('Call the pharmacy counter during opening hours.') }}</p>
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $pharmacy['phone']) }}">{{ $pharmacy['phone'] }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Medical disclaimer -->
            <div class="disclaimer" role="note">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                <div>
                    <strong>{{ __('Medical emergency? Call 999 or go to the nearest hospital.') }}</strong>
                    {{ __('PharmaTrack supports your pharmacist\'s care and does not replace advice from a doctor. Do not stop or change any medicine without speaking to your pharmacist or doctor first.') }}
                </div>
            </div>

        </main>

        <x-footer />
    </div>

    <!-- ── Kiosk Scanning Screen ── -->
    <div class="kiosk">
        <header class="header">
            <div class="logo">
                <x-brand :size="48" :tagline="__('Smart Pharmacy System')" />
            </div>
        </header>

        <section class="main" aria-label="Face check-in">
            <!-- Left Info Panel on Scanning Screen -->
            <div class="glass-card" style="max-width: 440px; width: 100%;">
                <h2 class="section-headline" style="font-size: clamp(2rem, 5vw, 2.5rem);">{!! __('Ready to verify your <span>identity</span>') !!}</h2>

                <ol class="kiosk-steps">
                    <li class="kiosk-step"><span class="kiosk-step-num" aria-hidden="true">1</span>{{ __('Face the camera directly') }}</li>
                    <li class="kiosk-step"><span class="kiosk-step-num" aria-hidden="true">2</span>{{ __('Turn your head to the side shown, then look back') }}</li>
                    <li class="kiosk-step"><span class="kiosk-step-num" aria-hidden="true">3</span>{{ __('Records retrieved instantly') }}</li>
                </ol>
            </div>

            <div class="cam-panel">
                <div class="cam-frame" id="cam-frame">
                    <div class="scan-line" id="scan-line"></div>
                    <video id="video" autoplay muted playsinline></video>
                    <canvas id="overlay-canvas"></canvas>
                </div>
                
                <div class="status-badge" id="status-badge" role="status" aria-live="polite">
                    <div class="status-dot" id="status-dot" aria-hidden="true"></div>
                    <span id="status-text">{{ __('Initializing AI Engine…') }}</span>
                </div>

                <button type="button" class="btn-retry" id="btn-retry">{{ __('Try again') }}</button>

                <div class="timeout-wrap">
                    <span id="timeout-label">30s</span>
                    <div id="timeout-bar"><div id="timeout-fill"></div></div>
                </div>

                <div class="scan-actions">
                    <button type="button" class="btn-back" id="btn-back">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                        {{ __('Cancel & Return') }}
                    </button>
                    <a href="{{ route('login') }}" class="btn-login-instead">
                        {{ __('Login Instead') }}
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </section>
        <x-footer>{{ __('Facial recognition data is used strictly for localized patient identification.') }}</x-footer>
    </div>

    <script>
    // Kiosk status messages come from face-utils.js and this script in English;
    // t() swaps them for the chosen language (falls back to English when not listed).
    @php
    $kioskStrings = [
        'No face detected — please look at the camera.', 'Face unclear — improve lighting and hold still.',
        'Please move closer to the camera.', 'Please face the camera directly.', 'Look straight at the camera.',
        'Other way — turn your head to your LEFT.', 'Other way — turn your head to your RIGHT.',
        'Slowly turn your head to your LEFT and hold until the beep.', 'Slowly turn your head to your RIGHT and hold until the beep.',
        'Turn ✓ — now look straight at the camera.', 'Liveness confirmed ✓',
        'Loading AI models…', 'Could not load the face recognition engine. Check the connection and try again.',
        'Timeout: Face not recognised.', 'System live — scanning for faces…',
        'Could not start the camera. Allow camera access, then try again.',
        'Liveness confirmed ✓ — hold still and look at the camera…', 'Face changed — please do the head turn again.',
        'Could not get a clear view — face the camera and do the head turn again.', 'Liveness confirmed ✓ — hold still…',
        'Verifying identity…', 'Too many attempts — please wait a moment.',
        'Not recognised yet — look straight at the camera and do the head turn again.', 'Could not reach the server.',
        'Patient identified! Redirecting…', 'Welcome, :name!',
    ];
    $kioskT = collect($kioskStrings)->mapWithKeys(fn ($s) => [$s => __($s)]);
    @endphp
    const LOCALE = @json(app()->getLocale());
    const KIOSK_T = @json($kioskT);
    function t(msg, vars = {}) {
        const count = msg.match(/ \(\d+\/\d+\)$/);           // "hold still… (2/3)"
        const base = count ? msg.slice(0, -count[0].length) : msg;
        let out = KIOSK_T[base] ?? base;
        for (const [k, v] of Object.entries(vars)) out = out.replace(`:${k}`, () => v);
        return count ? out + count[0] : out;
    }

    const clockEl = document.getElementById('clock');
    const tick = () => {
        const now = new Date();
        const loc = LOCALE === 'ms' ? 'ms-MY' : 'en-MY';
        clockEl.textContent = now.toLocaleDateString(loc, { weekday:'short', day:'numeric', month:'short' })
            + ' · ' + now.toLocaleTimeString(loc, { hour:'2-digit', minute:'2-digit' });
    };
    tick(); setInterval(tick, 1000);

    // face-api.js is large, so it is only downloaded when someone actually starts a check-in
    // (or hovers/focuses the button, to get a head start).
    let faceLibsPromise = null;
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = () => reject(new Error(`Failed to load ${src}`));
            document.head.appendChild(s);
        });
    }
    function loadFaceLibs() {
        if (!faceLibsPromise) {
            faceLibsPromise = loadScript('{{ asset("js/face-api.min.js") }}')
                .then(() => loadScript('{{ asset("js/face-utils.js") }}?v={{ filemtime(public_path('js/face-utils.js')) }}'))
                .catch((e) => { faceLibsPromise = null; throw e; }); // allow a retry after a network error
        }
        return faceLibsPromise;
    }

    const startBtn = document.getElementById('btn-start-verification');
    ['pointerenter', 'focus', 'touchstart'].forEach(evt =>
        startBtn.addEventListener(evt, () => { loadFaceLibs().catch(() => {}); }, { once: true, passive: true }));

    startBtn.addEventListener('click', async () => {
        document.getElementById('welcome-screen').style.display = 'none';
        document.querySelector('.kiosk').style.display = 'flex';
        document.body.classList.add('scanning');
        await startVerification();
    });

    // Non-blocking message (an alert() would freeze an unattended kiosk until someone clicks OK).
    function showToast(message, ms = 5000) {
        const toast = document.getElementById('verify-toast');
        document.getElementById('toast-msg').textContent = t(message);
        toast.style.display = 'block';
        clearTimeout(window._toastTimer);
        window._toastTimer = setTimeout(() => { toast.style.display = 'none'; }, ms);
    }

    // Each start gets a new session number; anything left over from an earlier attempt checks it and stops.
    window._kioskSession = 0;

    // Stops the camera and every timer of the current attempt.
    function stopScan() {
        window._kioskSession++;
        if (window._kioskStream) {
            window._kioskStream.getTracks().forEach(t => t.stop());
            window._kioskStream = null;
        }
        if (window._scanInterval)  { clearInterval(window._scanInterval);  window._scanInterval  = null; }
        if (window._verifyTimeout) { clearTimeout(window._verifyTimeout);  window._verifyTimeout = null; }
        if (window._countdownInt)  { clearInterval(window._countdownInt);  window._countdownInt  = null; }

        const fill = document.getElementById('timeout-fill');
        if (fill) fill.style.width = '100%';
        document.getElementById('btn-retry').classList.remove('show');
    }

    function cancelVerification(toastMsg = null) {
        stopScan();
        document.querySelector('.kiosk').style.display = 'none';
        document.getElementById('welcome-screen').style.display = 'flex';
        document.body.classList.remove('scanning');

        if (toastMsg) { showToast(toastMsg); }
    }

    document.getElementById('btn-back').addEventListener('click', () => { cancelVerification(); });
    document.getElementById('btn-retry').addEventListener('click', () => { startVerification(); });

    async function startVerification() {
        stopScan(); // clean up anything left from a previous attempt (e.g. after "Try again")
        const session = ++window._kioskSession;
        const isStale = () => session !== window._kioskSession;
        const video = document.getElementById('video');
        const canvas = document.getElementById('overlay-canvas');
        const statusBadge = document.getElementById('status-badge');
        const statusText = document.getElementById('status-text');
        const statusDot = document.getElementById('status-dot');
        const scanLine = document.getElementById('scan-line');
        const successOv = document.getElementById('success-overlay');
        const successName = document.getElementById('success-name');
        const retryBtn = document.getElementById('btn-retry');
        scanLine.style.display = '';

        // Matching happens on the server: we only send the descriptor of the face in front of the camera.
        const IDENTIFY_INTERVAL_MS = 2000;
        let modelsReady = false;
        let redirecting = false;
        let lastIdentifyAt = 0;

        setStatus('Loading AI models…', 'info');
        try {
            await loadFaceLibs();
            if (isStale()) return;
            await FaceUtils.loadModels('{{ asset("models") }}');
        } catch (e) {
            if (isStale()) return;
            setStatus('Could not load the face recognition engine. Check the connection and try again.', 'error');
            retryBtn.classList.add('show');
            return;
        }
        if (isStale()) return; // user cancelled while the models were loading

        modelsReady = true;

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
                if (isStale()) { stream.getTracks().forEach(t => t.stop()); return; }
                window._kioskStream = stream;
                video.srcObject = stream;
                setStatus('System live — scanning for faces…', 'info');
            } catch (e) {
                if (isStale()) return;
                setStatus('Could not start the camera. Allow camera access, then try again.', 'error');
                retryBtn.classList.add('show');
            }
        }

        // Replace (not add to) the previous attempt's handler, otherwise every retry starts another scan loop.
        if (window._kioskPlayHandler) video.removeEventListener('play', window._kioskPlayHandler);
        window._kioskPlayHandler = () => {
            if (isStale()) return;
            if (window._scanInterval) clearInterval(window._scanInterval);
            const displaySize = { width: video.clientWidth, height: video.clientHeight };
            canvas.width = displaySize.width;
            canvas.height = displaySize.height;
            faceapi.matchDimensions(canvas, displaySize);

            // Liveness + identification state
            const challenge = new FaceUtils.HeadTurnChallenge(() => FaceUtils.beep()); // beep = "turn confirmed, look back"
            const REQUIRED_FRAMES = 3;          // frontal frames sent to the server together
            const tick = FaceUtils.fpsMeter();
            let phase = 'liveness';             // 'liveness' (fast models, head-turn challenge) -> 'describe' (fingerprint frames)
            let recent = [];                    // descriptors collected in the 'describe' phase
            let describeAttempts = 0;
            let missedFrames = 0;

            const resetLiveness = () => { challenge.reset(); recent = []; describeAttempts = 0; missedFrames = 0; phase = 'liveness'; };
            const debug = (msg) => FaceUtils.DEBUG
                ? `${t(msg)}  [${tick()} fps, turn ${challenge.lastYaw?.toFixed(2) ?? '-'} (+ = your left), ask ${challenge.direction}, stage ${challenge.stage}]`
                : msg;

            const drawBox = (detection, ok) => {
                const currentSize = { width: video.clientWidth, height: video.clientHeight };
                if (canvas.width !== currentSize.width) {
                    canvas.width = currentSize.width;
                    canvas.height = currentSize.height;
                    faceapi.matchDimensions(canvas, currentSize);
                }
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (!detection) return;
                const box = faceapi.resizeResults(detection, currentSize).detection.box;
                ctx.strokeStyle = ok ? '#14b8a6' : '#f59e0b';
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);
            };

            let busy = false;
            // Runs as fast as the PC allows (the 'liveness' phase uses the cheap models).
            const scanId = window._scanInterval = setInterval(async () => {
                if (isStale()) { clearInterval(scanId); return; }
                if (busy || !modelsReady || redirecting) return;
                busy = true;

                try {
                    if (phase === 'liveness') {
                        const detection = await FaceUtils.detectFast(video);
                        const quality = FaceUtils.checkQuality(detection, video.videoWidth, { allowTurn: true });
                        const passedNow = detection && quality.ok ? challenge.update(detection.landmarks) : challenge.passed;

                        if (!detection) {
                            drawBox(null);
                            // Face gone for a while: someone else may step in, so start the liveness check over.
                            if (++missedFrames > 8) resetLiveness();
                            setStatus(debug('No face detected — please look at the camera.'), 'info');
                            return;
                        }
                        missedFrames = 0;

                        drawBox(detection, quality.ok);
                        if (!quality.ok) {
                            setStatus(debug(quality.reason), 'info');
                            return;
                        }

                        if (passedNow) {
                            phase = 'describe';
                            missedFrames = 0;
                            setStatus(debug('Liveness confirmed ✓ — hold still and look at the camera…'), 'info');
                        } else {
                            setStatus(debug(challenge.prompt()), 'info');
                        }
                        return;
                    }

                    // phase === 'describe': fingerprint a few frontal frames with eyes open.
                    const result = await FaceUtils.describe(video);
                    describeAttempts++;
                    if (!result && ++missedFrames > 3) {
                        // Face left after the challenge: whoever steps in must do it again.
                        resetLiveness();
                        drawBox(null);
                        setStatus(debug('No face detected — please look at the camera.'), 'info');
                        return;
                    }
                    const quality = FaceUtils.checkQuality(result, video.videoWidth);
                    drawBox(result, quality.ok);

                    const eyesOpen = result && FaceUtils.averageEar(result.landmarks) > 0.18; // skip mid-blink frames
                    if (quality.ok && eyesOpen) {
                        const d = Array.from(result.descriptor);
                        if (recent.length && FaceUtils.euclidean(d, recent[0]) > 0.45) {
                            resetLiveness(); // a different face appeared
                            setStatus(debug('Face changed — please do the head turn again.'), 'info');
                            return;
                        }
                        recent.push(d);
                    }

                    if (recent.length < REQUIRED_FRAMES) {
                        if (describeAttempts > 10) {
                            resetLiveness();
                            setStatus(debug('Could not get a clear view — face the camera and do the head turn again.'), 'info');
                        } else {
                            setStatus(debug(`Liveness confirmed ✓ — hold still… (${recent.length}/${REQUIRED_FRAMES})`), 'info');
                        }
                        return;
                    }

                    // Don't hammer the server: one identification request every couple of seconds.
                    if (Date.now() - lastIdentifyAt < IDENTIFY_INTERVAL_MS) return;
                    lastIdentifyAt = Date.now();

                    setStatus('Verifying identity…', 'info');
                    let data = null;
                    try {
                        const res = await fetch('{{ route("kiosk.identify") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ descriptors: recent.slice(0, 5) }),
                        });
                        if (res.status === 429) {
                            setStatus('Too many attempts — please wait a moment.', 'error');
                        } else if (res.ok) {
                            data = await res.json();
                        } else {
                            setStatus('Not recognised yet — look straight at the camera and do the head turn again.', 'info');
                        }
                    } catch (e) {
                        setStatus('Could not reach the server.', 'error');
                    }

                    if (data && data.matched) {
                        redirecting = true;
                        clearInterval(window._scanInterval);
                        clearTimeout(window._verifyTimeout);
                        clearInterval(window._countdownInt);
                        setStatus('Patient identified! Redirecting…', 'success');
                        scanLine.style.display = 'none';

                        successName.textContent = t('Welcome, :name!', { name: data.name });
                        successOv.classList.add('show');

                        setTimeout(() => { window.location.href = data.redirect; }, 2000);
                    } else {
                        resetLiveness(); // require a fresh head turn before the next attempt
                    }
                } finally {
                    busy = false;
                }
            }, 30);
        };
        video.addEventListener('play', window._kioskPlayHandler);

        function setStatus(msg, state) {
            statusText.textContent = t(msg);
            statusBadge.className = `status-badge ${state}`;
        }
    }
    </script>
</body>
</html>
