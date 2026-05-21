<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $patient->user->name }} — Health Summary · PharmaTrack</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @php
        $summaryBgVersion = file_exists(storage_path('app/public/bg.png'))
            ? filemtime(storage_path('app/public/bg.png'))
            : time();
        $summaryBgUrl = asset('storage/bg.png') . '?v=' . $summaryBgVersion;
    @endphp
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
            --danger:   #ef4444;
            --warn:     #f59e0b;
            --green:    #22c55e;
            --shadow-sm: 0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
            --shadow:    0 4px 16px rgba(15,23,42,.08), 0 2px 6px rgba(15,23,42,.05);
            --shadow-lg: 0 12px 40px rgba(15,23,42,.1), 0 4px 14px rgba(15,23,42,.06);
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            background: #eef7f8;
            color: var(--text);
            min-height: 100vh;
        }

        /* Background */
        .bg-scene {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                linear-gradient(120deg, rgba(255,255,255,.30), rgba(238,247,248,.66)),
                url("{{ $summaryBgUrl }}"),
                radial-gradient(ellipse 70% 50% at 10% 0%,  rgba(13,148,136,.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 45% at 90% 100%, rgba(6,182,212,.10) 0%, transparent 55%),
                linear-gradient(160deg, #f4fbfb 0%, #e7f3fb 100%);
            background-position: center;
            background-size: cover;
        }
        .bg-scene::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,.68), rgba(255,255,255,.50));
            opacity: .88;
        }
        .bg-scene::after {
            content: "";
            position: absolute;
            inset: auto 0 0 0;
            height: 260px;
            background:
                linear-gradient(180deg, transparent, rgba(255,255,255,.72)),
                repeating-linear-gradient(90deg, rgba(13,148,136,.08) 0 1px, transparent 1px 72px);
            opacity: .55;
        }
        .bg-dots {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(13,148,136,.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(13,148,136,.055) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(180deg, rgba(0,0,0,.85), rgba(0,0,0,.18));
            opacity: .75;
        }

        /* Layout */
        .page { position: relative; z-index: 1; max-width: 960px; margin: 0 auto; padding: 24px 20px 60px; }

        /* Header */
        .top-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 28px;
        }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, var(--teal), var(--cyan));
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(13,148,136,.3);
            color: #fff;
        }
        .logo-text { font-size: 1.1rem; font-weight: 800; color: var(--text); letter-spacing: -.02em; }

        .top-bar-actions { display: flex; gap: 10px; }
        .btn-ghost {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 10px; font-size: .83rem; font-weight: 600;
            border: 1px solid var(--border2); background: var(--surface);
            color: var(--muted); cursor: pointer; text-decoration: none;
            transition: all .2s; box-shadow: var(--shadow-sm);
        }
        .btn-ghost:hover { background: var(--surface2); color: var(--text); border-color: var(--border2); }
        .btn-primary {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 18px; border-radius: 10px; font-size: .83rem; font-weight: 700;
            background: linear-gradient(135deg, var(--teal), var(--cyan));
            color: #fff; text-decoration: none; border: none; cursor: pointer;
            transition: all .2s; box-shadow: 0 4px 14px rgba(13,148,136,.3);
        }
        .btn-primary:hover { opacity: .88; box-shadow: 0 6px 18px rgba(13,148,136,.4); }

        /* Hero card */
        .hero-card {
            position: relative;
            border-radius: 22px; overflow: hidden; margin-bottom: 24px;
            background:
                linear-gradient(120deg, rgba(6,78,59,.82) 0%, rgba(8,145,178,.68) 58%, rgba(15,23,42,.42) 100%),
                url("{{ $summaryBgUrl }}");
            background-position: center;
            background-size: cover;
            border: 1px solid rgba(13,148,136,.2);
            box-shadow: var(--shadow-lg), 0 0 0 1px rgba(13,148,136,.08);
        }
        .hero-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(15,118,110,.24), rgba(255,255,255,.04));
            pointer-events: none;
        }
        .hero-inner {
            position: relative; z-index: 1;
            display: flex; align-items: center; gap: 24px;
            padding: 28px 32px;
        }
        .avatar {
            width: 80px; height: 80px; border-radius: 50%; flex-shrink: 0;
            border: 3px solid rgba(255,255,255,.35);
            box-shadow: 0 0 0 5px rgba(255,255,255,.12);
        }
        .hero-name { font-size: 1.9rem; font-weight: 800; letter-spacing: -.03em; color: #fff; }
        .hero-meta { display: flex; gap: 12px; margin-top: 6px; flex-wrap: wrap; }
        .hero-meta span {
            font-size: .8rem; font-weight: 600; color: rgba(255,255,255,.75);
            background: rgba(255,255,255,.15); padding: 3px 12px; border-radius: 20px;
        }
        .hero-badge {
            margin-left: auto; flex-shrink: 0;
            display: flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: 30px;
            font-size: .8rem; font-weight: 700;
            background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25); color: #fff;
        }
        .hero-badge-dot { width:8px; height:8px; border-radius:50%; background:#4ade80; animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }

        /* Kiosk session notice */
        .kiosk-notice {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 18px; margin-bottom: 20px; border-radius: 12px;
            background: rgba(6,182,212,.06); border: 1px solid rgba(6,182,212,.2);
            font-size: .82rem; color: #0891b2;
            box-shadow: var(--shadow-sm);
        }

        /* Stat grid */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
        @media (max-width: 700px) { .stat-grid { grid-template-columns: repeat(2,1fr); } }
        .stat-card {
            border-radius: 18px; padding: 20px 22px;
            background: var(--surface); border: 1px solid var(--border);
            box-shadow: var(--shadow-sm); transition: box-shadow .2s;
        }
        .stat-card:hover { box-shadow: var(--shadow); }
        .stat-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--muted2); margin-bottom: 10px; }
        .stat-value { font-size: 2rem; font-weight: 800; letter-spacing: -.03em; color: var(--text); }
        .stat-sub   { font-size: .78rem; color: var(--muted); margin-top: 4px; }
        .badge-pill {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: .72rem; font-weight: 700; margin-top: 6px;
        }
        .pill-green  { background: rgba(34,197,94,.12);  color: #16a34a; }
        .pill-yellow { background: rgba(245,158,11,.12); color: #b45309; }
        .pill-red    { background: rgba(239,68,68,.12);  color: #dc2626; }
        .pill-teal   { background: rgba(13,148,136,.12); color: var(--teal-d); }

        /* Two-column section */
        .two-col { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
        @media (max-width: 700px) { .two-col { grid-template-columns: 1fr; } }

        /* Section card */
        .section-card {
            border-radius: 18px; background: var(--surface); border: 1px solid var(--border);
            overflow: hidden; margin-bottom: 20px; box-shadow: var(--shadow-sm);
        }
        .section-head {
            padding: 16px 22px; border-bottom: 1px solid var(--border);
            font-size: .95rem; font-weight: 700; color: var(--text);
            background: var(--surface2);
        }
        .section-body { padding: 20px 22px; }

        /* Info rows */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .info-row { display: flex; flex-direction: column; gap: 4px; }
        .info-key { font-size: .7rem; color: var(--muted2); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
        .info-val { font-size: .93rem; font-weight: 600; color: var(--text); }

        /* Medication items */
        .med-item {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 14px; border-radius: 12px;
            background: var(--surface2); border: 1px solid var(--border);
            margin-bottom: 10px; transition: box-shadow .2s;
        }
        .med-item:hover { box-shadow: var(--shadow-sm); }
        .med-icon {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, var(--teal), var(--cyan));
            display: flex; align-items: center; justify-content: center; font-size: 17px;
            box-shadow: 0 3px 10px rgba(13,148,136,.25);
        }
        .med-name   { font-size: .9rem; font-weight: 700; color: var(--text); }
        .med-detail { font-size: .78rem; color: var(--muted); margin-top: 2px; }

        /* Alert items */
        .alert-item {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 14px; border-radius: 12px;
            background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.18);
            color: #dc2626; font-size: .85rem; font-weight: 600;
            margin-bottom: 10px;
        }
        .alert-ok {
            padding: 16px; border-radius: 12px; text-align: center;
            background: rgba(34,197,94,.07); border: 1px solid rgba(34,197,94,.2);
            color: #16a34a; font-size: .85rem; font-weight: 600;
        }

        .intelligence-grid { display: grid; grid-template-columns: 1.05fr .95fr; gap: 18px; margin-bottom: 24px; }
        @media (max-width: 760px) { .intelligence-grid { grid-template-columns: 1fr; } }
        .ai-card {
            border-radius: 20px; background: var(--surface); border: 1px solid var(--border);
            box-shadow: var(--shadow); overflow: hidden;
        }
        .ai-card-head {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 18px 22px; border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(13,148,136,.08), rgba(6,182,212,.07));
        }
        .ai-title { font-size: 1rem; font-weight: 800; color: var(--text); }
        .ai-subtitle { margin-top: 3px; font-size: .78rem; color: var(--muted); font-weight: 500; }
        .risk-chip {
            display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 999px;
            font-size: .75rem; font-weight: 800; white-space: nowrap;
        }
        .risk-low { background: rgba(34,197,94,.12); color: #15803d; }
        .risk-moderate { background: rgba(245,158,11,.14); color: #b45309; }
        .risk-high { background: rgba(239,68,68,.12); color: #dc2626; }
        .ai-card-body { padding: 22px; }
        .risk-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 18px; }
        @media (max-width: 560px) { .risk-metrics { grid-template-columns: 1fr; } }
        .risk-metric {
            border: 1px solid var(--border); border-radius: 14px; padding: 14px;
            background: var(--surface2);
        }
        .risk-metric-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted2); font-weight: 800; }
        .risk-metric-value { margin-top: 6px; font-size: 1.55rem; font-weight: 800; color: var(--text); letter-spacing: -.03em; }
        .summary-box {
            padding: 15px 16px; border-radius: 14px; background: rgba(13,148,136,.07);
            border: 1px solid rgba(13,148,136,.16); color: #115e59; font-size: .88rem; line-height: 1.55;
        }
        .factor-list { display: grid; gap: 10px; margin-top: 16px; }
        .factor-item {
            display: flex; align-items: center; gap: 9px; padding: 11px 12px;
            border-radius: 12px; background: var(--surface2); border: 1px solid var(--border);
            color: var(--text); font-size: .84rem; font-weight: 700;
        }
        .factor-check {
            width: 20px; height: 20px; border-radius: 50%; display: inline-flex;
            align-items: center; justify-content: center; background: rgba(13,148,136,.12);
            color: var(--teal-d); font-size: .78rem; font-weight: 900; flex-shrink: 0;
        }
        .chart-box { position: relative; height: 300px; width: 100%; }

        /* Session timer */
        #session-timer {
            position: fixed; bottom: 20px; right: 24px; z-index: 50;
            display: flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: 30px;
            background: var(--surface); border: 1px solid var(--border);
            font-size: .8rem; color: var(--muted);
            box-shadow: var(--shadow);
        }
        #timer-count { font-weight: 700; color: var(--teal); }
        #timer-count.warn   { color: #b45309; }
        #timer-count.danger { color: #dc2626; }
    </style>
</head>
<body>

@php
    $latestCheckup       = $patient->healthCheckups->first();
    $medicalHistory      = $patient->medicalHistory;
    $activeMedCount      = $patient->medications->count();
    $bmi                 = (float) $patient->bmi;
    $patientName         = $patient->user->name ?: ('Patient #' . $patient->id);

    if ($bmi >= 30)       { $bmiLabel = 'Obese';       $bmiPill = 'pill-red'; }
    elseif ($bmi >= 25)   { $bmiLabel = 'Overweight';  $bmiPill = 'pill-yellow'; }
    elseif ($bmi < 18.5)  { $bmiLabel = 'Underweight'; $bmiPill = 'pill-yellow'; }
    else                  { $bmiLabel = 'Healthy';      $bmiPill = 'pill-green'; }

    $riskFlags = collect([
        optional($medicalHistory)->hypertension === 'High Risk' ? 'High blood pressure risk' : null,
        filled(optional($medicalHistory)->diabetes) && optional($medicalHistory)->diabetes !== 'None'
            ? optional($medicalHistory)->diabetes : null,
        filled(optional($medicalHistory)->allergies)      ? 'General allergies recorded' : null,
        filled(optional($medicalHistory)->drug_allergies) ? 'Drug allergies recorded'    : null,
        $latestCheckup && filled($latestCheckup->blood_pressure)
            ? 'Latest BP: ' . $latestCheckup->blood_pressure : null,
    ])->filter()->values();

    $chartData         = $patient->healthCheckups->sortBy('checkup_date')->values();
    $chartLabels       = $chartData->pluck('checkup_date')->map(fn ($date) => \Carbon\Carbon::parse($date)->format('d M y'))->values();
    $sugarSeries       = $chartData->pluck('blood_sugar')->values();
    $cholesterolSeries = $chartData->pluck('cholesterol')->values();
    $prediction = $prediction ?? [
        'success' => false,
        'risk' => 'Not Available',
        'risk_label' => 'Not Available',
        'confidence' => '0%',
        'risk_score' => 0,
        'factors' => [],
        'summary' => 'Prediction data is not available.',
        'inputs' => ['bmi' => 0, 'blood_sugar' => 0, 'blood_pressure' => 0, 'cholesterol' => 0, 'lifestyle_score' => 0],
    ];
    $riskClass = match ($prediction['risk']) {
        'High' => 'risk-high',
        'Moderate' => 'risk-moderate',
        default => 'risk-low',
    };
@endphp

<div class="bg-scene"></div>
<div class="bg-dots"></div>

<div class="page">

    <!-- Top bar -->
    <div class="top-bar">
        <a href="{{ route('welcome') }}" class="logo">
            <div class="logo-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path d="M10 4h4v6h6v4h-6v6h-4v-6H4v-4h6V4z" stroke-linejoin="round"/>
                </svg>
            </div>
            <span class="logo-text">PharmaTrack</span>
        </a>
        <div class="top-bar-actions">
            <a href="{{ route('welcome') }}" class="btn-ghost">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to Kiosk
            </a>
            <a href="{{ route('pharmacist.patients.summary.download', $patient->id) }}" class="btn-primary">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download PDF
            </a>
        </div>
    </div>

    <!-- Kiosk notice -->
    <div class="kiosk-notice">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
        You are viewing this page via <strong>&nbsp;face recognition&nbsp;</strong>. This session expires in <strong>&nbsp;<span id="session-expire-notice">10 minutes</span></strong>.
    </div>

    <!-- Hero -->
    <div class="hero-card">
        <div class="hero-inner">
            <img class="avatar"
                 src="https://ui-avatars.com/api/?name={{ urlencode($patientName) }}&background=14b8a6&color=fff&size=128&bold=true"
                 alt="{{ $patientName }}">
            <div>
                <div style="font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:rgba(255,255,255,.5); margin-bottom:4px;">
                    Kiosk Health Summary
                </div>
                <div class="hero-name">{{ $patientName }}</div>
                <div class="hero-meta">
                    <span>{{ $patient->gender }}</span>
                    <span>{{ $patient->age }} years old</span>
                    <span>{{ $patient->height }} cm · {{ $patient->weight }} kg</span>
                </div>
            </div>
            <div class="hero-badge">
                <div class="hero-badge-dot"></div>
                Face Verified
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">BMI</div>
            <div class="stat-value">{{ number_format($bmi, 1) }}</div>
            <span class="badge-pill {{ $bmiPill }}">{{ $bmiLabel }}</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">Blood Pressure</div>
            <div class="stat-value" style="font-size:1.4rem;">{{ $latestCheckup?->blood_pressure ?? '—' }}</div>
            <div class="stat-sub">
                {{ $latestCheckup ? \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') : 'No checkup recorded' }}
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Blood Sugar</div>
            <div class="stat-value" style="font-size:1.4rem;">{{ $latestCheckup?->blood_sugar ?? '—' }}</div>
            <div class="stat-sub">Cholesterol: {{ $latestCheckup?->cholesterol ?? 'N/A' }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Medications</div>
            <div class="stat-value">{{ $activeMedCount }}</div>
            <span class="badge-pill pill-teal">{{ $activeMedCount > 0 ? 'On prescription' : 'None recorded' }}</span>
        </div>
    </div>

    <div class="intelligence-grid">
        <div class="ai-card">
            <div class="ai-card-head">
                <div>
                    <div class="ai-title">AI Health Intelligence</div>
                    <div class="ai-subtitle">Decision Tree prediction generated after face recognition</div>
                </div>
                <span class="risk-chip {{ $riskClass }}">{{ $prediction['risk_label'] }}</span>
            </div>
            <div class="ai-card-body">
                <div class="risk-metrics">
                    <div class="risk-metric">
                        <div class="risk-metric-label">Risk Level</div>
                        <div class="risk-metric-value" style="font-size:1.25rem;">{{ $prediction['risk_label'] }}</div>
                    </div>
                    <div class="risk-metric">
                        <div class="risk-metric-label">Confidence</div>
                        <div class="risk-metric-value">{{ $prediction['confidence'] }}</div>
                    </div>
                    <div class="risk-metric">
                        <div class="risk-metric-label">Risk Score</div>
                        <div class="risk-metric-value">{{ $prediction['risk_score'] }}/100</div>
                    </div>
                </div>

                <div class="summary-box">{{ $prediction['summary'] }}</div>

                <div class="factor-list">
                    @forelse($prediction['factors'] as $factor)
                        <div class="factor-item"><span class="factor-check">&check;</span>{{ $factor }}</div>
                    @empty
                        <div class="factor-item"><span class="factor-check">&check;</span>No risk factors detected yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="ai-card">
            <div class="ai-card-head">
                <div>
                    <div class="ai-title">Health Radar</div>
                    <div class="ai-subtitle">BMI, sugar, pressure, cholesterol, and lifestyle score</div>
                </div>
            </div>
            <div class="ai-card-body">
                <div class="chart-box">
                    <canvas id="kioskRiskRadar"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-head">📈 Health Trends</div>
        <div class="section-body">
            @if($chartData->isNotEmpty())
                <p style="font-size:.82rem; color:var(--muted); margin-bottom:14px;">
                    This chart shows how blood sugar and cholesterol readings have changed across recorded check-ups.
                </p>
                <div style="position:relative; height:320px; width:100%;">
                    <canvas id="kioskHealthChart"></canvas>
                </div>
            @else
                <p style="color:var(--muted); font-size:.88rem; font-style:italic;">No check-up trend data is available yet.</p>
            @endif
        </div>
    </div>

    <!-- Two-column -->
    <div class="two-col">
        <!-- Left: Clinical info -->
        <div>
            <div class="section-card">
                <div class="section-head">📋 Medical History</div>
                <div class="section-body">
                    <div class="info-grid">
                        <div class="info-row">
                            <span class="info-key">Hypertension</span>
                            <span class="info-val">{{ optional($medicalHistory)->hypertension ?: 'None' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Diabetes</span>
                            <span class="info-val">{{ optional($medicalHistory)->diabetes ?: 'None' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Allergies</span>
                            <span class="info-val">{{ optional($medicalHistory)->allergies ?: 'No known allergies' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Drug Allergies</span>
                            <span class="info-val">{{ optional($medicalHistory)->drug_allergies ?: 'No known drug allergies' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-head">💊 Current Medications</div>
                <div class="section-body">
                    @forelse($patient->medications as $med)
                        <div class="med-item">
                            <div class="med-icon">💊</div>
                            <div>
                                <div class="med-name">{{ $med->name }}</div>
                                <div class="med-detail">{{ $med->dosage }}{{ $med->frequency ? ' · ' . $med->frequency : '' }}</div>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--muted); font-size:.88rem; font-style:italic;">No active medications recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Alerts + latest checkup -->
        <div>
            <div class="section-card">
                <div class="section-head">⚠️ Health Alerts</div>
                <div class="section-body">
                    @if($riskFlags->isNotEmpty())
                        @foreach($riskFlags as $flag)
                            <div class="alert-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                {{ $flag }}
                            </div>
                        @endforeach
                    @else
                        <div class="alert-ok">✓ No high-priority health alerts recorded</div>
                    @endif
                </div>
            </div>

            <div class="section-card">
                <div class="section-head">🩺 Latest Check-up</div>
                <div class="section-body">
                    @if($latestCheckup)
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <div class="info-row">
                                <span class="info-key">Date</span>
                                <span class="info-val">{{ \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-key">Blood Pressure</span>
                                <span class="info-val">{{ $latestCheckup->blood_pressure ?? 'N/A' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-key">Blood Sugar</span>
                                <span class="info-val">{{ $latestCheckup->blood_sugar ?? 'N/A' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-key">Cholesterol</span>
                                <span class="info-val">{{ $latestCheckup->cholesterol ?? 'N/A' }}</span>
                            </div>
                        </div>
                    @else
                        <p style="color:var(--muted); font-size:.88rem; font-style:italic;">No check-up records found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Session timer widget -->
<div id="session-timer">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    Session expires in <span id="timer-count">10:00</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Session countdown — 10 minutes (600s)
(function () {
    const startedAt = {{ session('kiosk_auth_at') ?? 'null' }};
    if (!startedAt) return;

    const expiresAt = startedAt + 600;
    const timerEl   = document.getElementById('timer-count');
    const noticeEl  = document.getElementById('session-expire-notice');

    function update() {
        const remaining = expiresAt - Math.floor(Date.now() / 1000);
        if (remaining <= 0) {
            window.location.href = '{{ route("welcome") }}';
            return;
        }
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        timerEl.textContent   = `${m}:${s}`;
        noticeEl.textContent  = `${m}:${s}`;
        timerEl.className     = remaining <= 60 ? 'danger' : remaining <= 120 ? 'warn' : '';
    }

    update();
    setInterval(update, 1000);
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radarCanvas = document.getElementById('kioskRiskRadar');

    if (radarCanvas) {
        const radarInputs = {!! json_encode($prediction['inputs']) !!};

        new Chart(radarCanvas.getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['BMI', 'Blood Sugar', 'Blood Pressure', 'Cholesterol', 'Lifestyle Score'],
                datasets: [{
                    label: 'Patient Health Profile',
                    data: [
                        Math.min((radarInputs.bmi / 40) * 100, 100),
                        Math.min((radarInputs.blood_sugar / 10) * 100, 100),
                        Math.min((radarInputs.blood_pressure / 180) * 100, 100),
                        Math.min((radarInputs.cholesterol / 8) * 100, 100),
                        radarInputs.lifestyle_score
                    ],
                    borderColor: 'rgb(13, 148, 136)',
                    backgroundColor: 'rgba(13, 148, 136, 0.16)',
                    pointBackgroundColor: 'rgb(6, 182, 212)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { display: false },
                        grid: { color: 'rgba(100, 116, 139, 0.18)' },
                        angleLines: { color: 'rgba(100, 116, 139, 0.18)' }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    const chartCanvas = document.getElementById('kioskHealthChart');

    if (!chartCanvas) return;

    const chartLabels = {!! json_encode($chartLabels) !!};
    const sugarSeries = {!! json_encode($sugarSeries) !!};
    const cholesterolSeries = {!! json_encode($cholesterolSeries) !!};

    new Chart(chartCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Blood Sugar (mmol/L)',
                    data: sugarSeries,
                    borderColor: 'rgb(13, 148, 136)',
                    backgroundColor: 'rgba(13, 148, 136, 0.10)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgb(13, 148, 136)',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.35
                },
                {
                    label: 'Cholesterol (mmol/L)',
                    data: cholesterolSeries,
                    borderColor: 'rgb(6, 182, 212)',
                    backgroundColor: 'rgba(6, 182, 212, 0.06)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgb(6, 182, 212)',
                    pointRadius: 4,
                    fill: false,
                    tension: 0.35
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
});
</script>
</body>
</html>
