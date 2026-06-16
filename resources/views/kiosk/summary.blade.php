<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $patient->user->name }} — Health Summary · PharmaTrack</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        teal: { 50: '#f0fdfa', 100: '#ccfbf1', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e' },
                        cyan: { 500: '#06b6d4', 600: '#0891b2' }
                    }
                }
            }
        }
    </script>
    @php
        $summaryBgVersion = file_exists(storage_path('app/public/bg.png'))
            ? filemtime(storage_path('app/public/bg.png'))
            : time();
        $summaryBgUrl = asset('storage/bg.png') . '?v=' . $summaryBgVersion;
    @endphp
    <style>
        /* Preserve complex background scenes and legacy badge classes returned by PHP */
        body { background-color: #eef7f8; }
        .bg-scene {
            position: fixed; inset: 0; z-index: -1; pointer-events: none;
            background: linear-gradient(120deg, rgba(255,255,255,.30), rgba(238,247,248,.66)),
                        url("{{ $summaryBgUrl }}"),
                        radial-gradient(ellipse 70% 50% at 10% 0%,  rgba(13,148,136,.12) 0%, transparent 60%),
                        radial-gradient(ellipse 60% 45% at 90% 100%, rgba(6,182,212,.10) 0%, transparent 55%),
                        linear-gradient(160deg, #f4fbfb 0%, #e7f3fb 100%);
            background-position: center; background-size: cover;
        }
        .bg-scene::before {
            content: ""; position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,.68), rgba(255,255,255,.50)); opacity: .88;
        }
        .bg-dots {
            position: fixed; inset: 0; z-index: -1; pointer-events: none;
            background-image: linear-gradient(rgba(13,148,136,.055) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(13,148,136,.055) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(180deg, rgba(0,0,0,.85), rgba(0,0,0,.18)); opacity: .75;
        }
        
        /* Required mapping for PHP Badge Closure */
        .rbadge-normal     { background-color: #dcfce7; color: #15803d; }
        .rbadge-borderline { background-color: #fef3c7; color: #b45309; }
        .rbadge-high       { background-color: #fee2e2; color: #b91c1c; }
        .rbadge-low        { background-color: #fee2e2; color: #b91c1c; }
        .rbadge-na         { background-color: #f1f5f9; color: #64748b; }

        /* Custom Scrollbar for details panel */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen">

@php
    $latestCheckup  = $patient->healthCheckups->first();
    $medicalHistory = $patient->medicalHistory;
    $activeMedCount = $patient->medications->count();
    $bmi            = (float) $patient->bmi;
    $patientName    = $patient->user->name ?: ('Patient #' . $patient->id);

    if ($bmi >= 30)      { $bmiLabel = 'Obese';       $bmiPill = 'bg-red-100 text-red-700'; }
    elseif ($bmi >= 25)  { $bmiLabel = 'Overweight';  $bmiPill = 'bg-amber-100 text-amber-700'; }
    elseif ($bmi < 18.5) { $bmiLabel = 'Underweight'; $bmiPill = 'bg-amber-100 text-amber-700'; }
    else                 { $bmiLabel = 'Healthy';     $bmiPill = 'bg-green-100 text-green-700'; }

    $riskFlags = collect([
        optional($medicalHistory)->hypertension === 'High Risk' ? 'High blood pressure risk' : null,
        filled(optional($medicalHistory)->diabetes) && optional($medicalHistory)->diabetes !== 'None' ? optional($medicalHistory)->diabetes : null,
        filled(optional($medicalHistory)->allergies)      ? 'General allergies recorded' : null,
        filled(optional($medicalHistory)->drug_allergies) ? 'Drug allergies recorded'    : null,
        $latestCheckup && filled($latestCheckup->blood_pressure) ? 'Latest BP: ' . $latestCheckup->blood_pressure : null,
        $latestCheckup && $latestCheckup->blood_sugar > 7.0 ? 'High blood sugar detected' : null,
        $latestCheckup && $latestCheckup->hba1c > 6.5       ? 'HbA1c above diabetic threshold' : null,
        $latestCheckup && $latestCheckup->cholesterol > 6.2  ? 'High total cholesterol' : null,
        $latestCheckup && $latestCheckup->ldl >= 2.6         ? 'LDL cholesterol outside normal range' : null,
    ])->filter()->values();

    $chartData         = $patient->healthCheckups->sortBy('checkup_date')->values();
    $chartLabels       = $chartData->pluck('checkup_date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M y'))->values();
    $sugarSeries       = $chartData->pluck('blood_sugar')->values();
    $cholesterolSeries = $chartData->pluck('cholesterol')->values();
    $ldlSeries         = $chartData->pluck('ldl')->values();
    $hba1cSeries       = $chartData->pluck('hba1c')->values();

    $radarBmi      = $bmi > 0 ? min(round(($bmi / 40) * 100), 100) : 0;
    $radarSugar    = $latestCheckup && is_numeric($latestCheckup->blood_sugar) ? min(round(((float) $latestCheckup->blood_sugar / 10) * 100), 100) : 0;
    $radarCholest = $latestCheckup && is_numeric($latestCheckup->cholesterol) ? min(round(((float) $latestCheckup->cholesterol / 8) * 100), 100) : 0;
    $systolic      = 0;
    if ($latestCheckup && filled($latestCheckup->blood_pressure)) {
        $systolic = (int) explode('/', $latestCheckup->blood_pressure)[0];
    }
    $radarBp        = $systolic > 0 ? min(round(($systolic / 180) * 100), 100) : 0;
    $radarAdherence = $activeMedCount > 0 && $latestCheckup ? min($activeMedCount * 15, 100) : 0;
    $hasRadarData   = ($radarBmi + $radarSugar + $radarCholest + $radarBp) > 0;

    $readingBadge = function($value, string $type): array {
        if ($value === null) return ['N/A', 'rbadge-na'];
        $v = (float) $value;
        return match($type) {
            'sugar'       => $v > 7.0  ? ['High', 'rbadge-high']       : ($v > 6.0  ? ['Borderline','rbadge-borderline'] : ['Normal','rbadge-normal']),
            'hba1c'       => $v > 6.5  ? ['High', 'rbadge-high']       : ($v > 5.7  ? ['Borderline','rbadge-borderline'] : ['Normal','rbadge-normal']),
            'cholesterol' => $v > 6.2  ? ['High', 'rbadge-high']       : ($v > 5.2  ? ['Borderline','rbadge-borderline'] : ['Normal','rbadge-normal']),
            'ldl'         => $v > 3.4  ? ['High', 'rbadge-high']       : ($v >= 2.6 ? ['Borderline','rbadge-borderline'] : ['Normal','rbadge-normal']),
            'hdl'         => $v < 1.0  ? ['Low',  'rbadge-low']        : ($v <= 1.3 ? ['Borderline','rbadge-borderline'] : ['Normal','rbadge-normal']),
            'hr'          => ($v < 60 || $v > 100) ? ($v < 50 || $v > 110 ? ['Abnormal','rbadge-high'] : ['Borderline','rbadge-borderline']) : ['Normal','rbadge-normal'],
            'haemoglobin' => ($v < 12 || $v > 16) ? ['Review','rbadge-borderline'] : ['Normal','rbadge-normal'],
            'ag_ratio'    => ($v < 1.1 || $v > 2.5) ? ['Review','rbadge-borderline'] : ['Normal','rbadge-normal'],
            'alp'         => ($v < 38 || $v > 124) ? ['Review','rbadge-borderline'] : ['Normal','rbadge-normal'],
            'ast'         => $v >= 34 ? ['Review','rbadge-borderline'] : ['Normal','rbadge-normal'],
            'alt'         => ($v < 10 || $v > 49) ? ['Review','rbadge-borderline'] : ['Normal','rbadge-normal'],
            'ggt'         => $v >= 38 ? ['Review','rbadge-borderline'] : ['Normal','rbadge-normal'],
            'sodium'      => ($v < 135 || $v > 145) ? ['Review','rbadge-borderline'] : ['Normal','rbadge-normal'],
            'renal_glucose'=> ($v < 3.9 || $v > 6.0) ? ['Review','rbadge-borderline'] : ['Normal','rbadge-normal'],
            default       => ['—', 'rbadge-na'],
        };
    };
@endphp

<div class="bg-scene"></div>
<div class="bg-dots"></div>

<main class="relative z-10 max-w-5xl mx-auto px-4 py-8 pb-24 lg:px-8">

    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <a href="{{ route('welcome') }}" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-teal-500/30 group-hover:scale-105 transition-transform">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10 4h4v6h6v4h-6v6h-4v-6H4v-4h6V4z" stroke-linejoin="round"/></svg>
            </div>
            <span class="text-xl font-extrabold text-slate-800 tracking-tight">PharmaTrack</span>
        </a>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to Kiosk
            </a>
            <a href="{{ route('kiosk.summary.download', $patient->id) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold bg-gradient-to-r from-teal-600 to-cyan-600 text-white hover:from-teal-700 hover:to-cyan-700 transition-all shadow-lg shadow-teal-500/25">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download PDF
            </a>
        </div>
    </header>

    <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-700 shadow-sm">
        <svg class="shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
        <span class="text-sm font-medium">You are viewing this page via <strong>face recognition</strong>. This session expires in <strong id="session-expire-notice">10 minutes</strong>.</span>
    </div>

    <section class="relative rounded-3xl overflow-hidden mb-8 shadow-xl shadow-slate-200/50 border border-teal-100 bg-gradient-to-br from-teal-900 via-cyan-800 to-slate-900">
        <div class="absolute inset-0 bg-[url('{{ $summaryBgUrl }}')] bg-cover bg-center mix-blend-overlay opacity-30"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start text-center md:text-left gap-6 p-8 md:p-10">
            
            <img src="https://ui-avatars.com/api/?name={{ urlencode($patientName) }}&background=14b8a6&color=fff&size=128&bold=true" 
                 alt="{{ $patientName }}" 
                 class="w-24 h-24 rounded-full border-4 border-white/30 shadow-2xl shrink-0">
            
            <div class="flex-1">
                <p class="text-xs font-bold uppercase tracking-widest text-teal-200 mb-1">Kiosk Health Summary</p>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-3">{{ $patientName }}</h1>
                <div class="flex flex-wrap justify-center md:justify-start gap-2">
                    <span class="px-4 py-1.5 rounded-full text-sm font-medium bg-white/10 text-white backdrop-blur-md border border-white/20">{{ $patient->gender }}</span>
                    <span class="px-4 py-1.5 rounded-full text-sm font-medium bg-white/10 text-white backdrop-blur-md border border-white/20">{{ $patient->age }} years old</span>
                    <span class="px-4 py-1.5 rounded-full text-sm font-medium bg-white/10 text-white backdrop-blur-md border border-white/20">{{ $patient->height }} cm · {{ $patient->weight }} kg</span>
                </div>
            </div>

            <div class="mt-4 md:mt-0 flex items-center gap-2 px-5 py-2.5 rounded-full bg-white/10 text-white border border-white/20 backdrop-blur-md font-bold text-sm shrink-0">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                Face Verified
            </div>
        </div>
    </section>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">BMI</p>
            <p class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ number_format($bmi, 1) }}</p>
            <span class="inline-block mt-2 px-3 py-1 text-xs font-bold rounded-full {{ $bmiPill }}">{{ $bmiLabel }}</span>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Blood Pressure</p>
            <p class="text-2xl font-extrabold text-slate-800 tracking-tight mt-1">{{ $latestCheckup?->blood_pressure ?? '—' }}</p>
            <p class="text-xs font-medium text-slate-500 mt-2">{{ $latestCheckup ? \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') : 'No checkup recorded' }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Blood Sugar</p>
            <p class="text-2xl font-extrabold text-slate-800 tracking-tight mt-1">{{ $latestCheckup?->blood_sugar ? number_format($latestCheckup->blood_sugar, 1) . ' mmol/L' : '—' }}</p>
            @php [$bl, $bc] = $readingBadge($latestCheckup?->blood_sugar, 'sugar'); @endphp
            <span class="inline-block mt-2 px-3 py-1 text-xs font-bold rounded-full {{ $bc === 'rbadge-normal' ? 'bg-green-100 text-green-700' : ($bc === 'rbadge-high' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $bl }}</span>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Active Meds</p>
            <p class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ $activeMedCount }}</p>
            <span class="inline-block mt-2 px-3 py-1 text-xs font-bold rounded-full bg-teal-50 text-teal-700">{{ $activeMedCount > 0 ? 'On prescription' : 'None recorded' }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm lg:col-span-2 overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="font-bold text-slate-800 text-base">Health Radar</h3>
            </div>
            <div class="p-6 flex-1 flex flex-col">
                <p class="text-sm text-slate-500 mb-4">Quick overview of current patient status across key markers.</p>
                <div class="relative w-full flex-1 min-h-[280px]">
                    @if($hasRadarData)
                        <canvas id="kioskRiskRadar"></canvas>
                    @else
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-slate-400">
                            <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <p class="font-bold text-slate-500">Insufficient data</p>
                            <p class="text-xs mt-1">Record a check-up to populate.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm lg:col-span-3 overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="font-bold text-slate-800 text-base">Key Health Trend</h3>
            </div>
            <div class="p-6 flex-1 flex flex-col">
                <p class="text-sm text-slate-500 mb-4">Historical readings for blood sugar, cholesterol, HbA1c, and LDL.</p>
                <div class="relative w-full flex-1 min-h-[280px]">
                    @if($chartData->count() >= 2)
                        <canvas id="kioskHealthChart"></canvas>
                    @else
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-slate-400">
                            <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                            <p class="font-bold text-slate-500">No historical data</p>
                            <p class="text-xs mt-1">Complete at least 2 check-ups to view trends.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            
            <div id="checkup-create-panel" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-2 text-slate-800">
                    <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <h3 class="font-bold text-base">Add Check-up</h3>
                </div>
                <div class="p-6">
                    @if(session('kiosk_checkup_success'))
                        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">{{ session('kiosk_checkup_success') }}</div>
                    @endif
                    @if($errors->kioskCheckup->any())
                        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold">Please check the new check-up form below.</div>
                    @endif

                    <div id="latest-checkup-slot"></div>

                    <details id="checkup-create-details" class="group" {{ $errors->kioskCheckup->any() ? 'open' : '' }}>
                        <summary class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-teal-500 to-cyan-500 text-white font-bold text-sm cursor-pointer hover:shadow-lg transition-all mt-4">
                            <span>Open Check-up Form</span>
                            <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <form action="{{ route('kiosk.checkups.store', $patient->id) }}" method="POST" class="space-y-8">
                                @csrf
                                
                                <div>
                                    <h4 class="text-sm font-extrabold text-slate-800 border-b border-slate-100 pb-2 mb-4">Visit Details</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5" for="kiosk-checkup-date">Date</label>
                                            <input id="kiosk-checkup-date" type="date" name="checkup_date" value="{{ old('checkup_date', now()->toDateString()) }}" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all" required>
                                            @error('checkup_date', 'kioskCheckup')<span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>@enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5" for="kiosk-report-source">Source</label>
                                            <select id="kiosk-report-source" name="report_source" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                                <option value="">Select source</option>
                                                @foreach(['klinik_kesihatan' => 'Klinik Kesihatan', 'private_clinic' => 'Private Clinic', 'hospital' => 'Hospital', 'private_lab' => 'Private Lab', 'home_device' => 'Home Device'] as $val => $lbl)
                                                    <option value="{{ $val }}" {{ old('report_source') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                                @endforeach
                                            </select>
                                            @error('report_source', 'kioskCheckup')<span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>@enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5" for="kiosk-checkup-code">Pharmacist Code</label>
                                            <input id="kiosk-checkup-code" type="password" name="pharmacist_code" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all" required>
                                            @error('pharmacist_code', 'kioskCheckup')<span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-extrabold text-slate-800 border-b border-slate-100 pb-2 mb-4">Patient Measurement</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Weight (kg)</label>
                                            <input type="number" step="0.1" name="patient_weight" value="{{ old('patient_weight') }}" placeholder="{{ $patient->weight }}" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                            @error('patient_weight', 'kioskCheckup')<span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>@enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Height (cm)</label>
                                            <input type="number" step="0.1" name="patient_height" value="{{ old('patient_height') }}" placeholder="{{ $patient->height }}" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                            @error('patient_height', 'kioskCheckup')<span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-2">Fill only when measurement needs updating.</p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-extrabold text-slate-800 border-b border-slate-100 pb-2 mb-4">Core Readings</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Blood Pressure</label>
                                            <input type="text" name="blood_pressure" value="{{ old('blood_pressure') }}" placeholder="120/80" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                            @error('blood_pressure', 'kioskCheckup')<span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>@enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Heart Rate</label>
                                            <input type="number" name="heart_rate" value="{{ old('heart_rate') }}" placeholder="75" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                            @error('heart_rate', 'kioskCheckup')<span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>@enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Haemoglobin</label>
                                            <input type="number" step="0.01" name="haemoglobin" value="{{ old('haemoglobin') }}" placeholder="13.5" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                            @error('haemoglobin', 'kioskCheckup')<span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div>
                                        <h4 class="text-sm font-extrabold text-slate-800 border-b border-slate-100 pb-2 mb-4">Blood Glucose</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fasting Sugar</label>
                                                <input type="number" step="0.01" name="blood_sugar" value="{{ old('blood_sugar') }}" placeholder="5.5" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">HbA1c (%)</label>
                                                <input type="number" step="0.1" name="hba1c" value="{{ old('hba1c') }}" placeholder="5.4" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-slate-800 border-b border-slate-100 pb-2 mb-4">Lipid Panel</h4>
                                        <div class="space-y-4">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Total Chol.</label>
                                                    <input type="number" step="0.01" name="cholesterol" value="{{ old('cholesterol') }}" placeholder="4.8" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">LDL</label>
                                                    <input type="number" step="0.01" name="ldl" value="{{ old('ldl') }}" placeholder="2.6" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">HDL</label>
                                                <input type="number" step="0.01" name="hdl" value="{{ old('hdl') }}" placeholder="1.3" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <details class="group bg-slate-50 border border-slate-200 rounded-xl">
                                        <summary class="flex items-center justify-between px-4 py-3 cursor-pointer font-bold text-sm text-slate-700">
                                            Liver & Renal Function Tests (Optional)
                                            <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </summary>
                                        <div class="p-4 border-t border-slate-200 bg-white rounded-b-xl">
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">A/G Ratio</label><input type="number" step="0.01" name="albumin_globulin_ratio" class="w-full rounded-lg border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">ALP</label><input type="number" step="0.01" name="alkaline_phosphatase" class="w-full rounded-lg border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">AST</label><input type="number" step="0.01" name="aspartate_transaminase" class="w-full rounded-lg border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">ALT</label><input type="number" step="0.01" name="alanine_transaminase" class="w-full rounded-lg border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">GGT</label><input type="number" step="0.01" name="gamma_glutamyl_transferase" class="w-full rounded-lg border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sodium</label><input type="number" step="0.01" name="sodium" class="w-full rounded-lg border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Renal Gluc.</label><input type="number" step="0.01" name="renal_glucose" class="w-full rounded-lg border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                                            </div>
                                        </div>
                                    </details>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Notes</label>
                                    <textarea name="notes" rows="3" class="w-full rounded-xl border-slate-200 bg-slate-50 border px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none transition-all" placeholder="Optional clinical notes">{{ old('notes') }}</textarea>
                                </div>

                                <div class="flex justify-end pt-4 border-t border-slate-100">
                                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 text-white font-bold hover:shadow-lg hover:shadow-teal-500/30 transition-all">Save New Check-up</button>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-2 text-slate-800">
                    <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <h3 class="font-bold text-base">Medical History</h3>
                </div>
                <div class="p-6">
                    @if(session('kiosk_medical_success'))
                        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">{{ session('kiosk_medical_success') }}</div>
                    @endif
                    @if($errors->kioskMedical->any())
                        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold">Please check the medical record form below.</div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                        <div><p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hypertension</p><p class="text-slate-800 font-semibold mt-1">{{ optional($medicalHistory)->hypertension ?: 'None' }}</p></div>
                        <div><p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Diabetes</p><p class="text-slate-800 font-semibold mt-1">{{ optional($medicalHistory)->diabetes ?: 'None' }}</p></div>
                        <div><p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Allergies</p><p class="text-slate-800 font-semibold mt-1">{{ optional($medicalHistory)->allergies ?: 'No known allergies' }}</p></div>
                        <div><p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Drug Allergies</p><p class="text-slate-800 font-semibold mt-1">{{ optional($medicalHistory)->drug_allergies ?: 'No known drug allergies' }}</p></div>
                        <div class="md:col-span-2"><p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Other Conditions</p><p class="text-slate-800 font-semibold mt-1">{{ optional($medicalHistory)->others ?: 'None recorded' }}</p></div>
                    </div>

                    <details class="group mt-6 pt-6 border-t border-slate-100" {{ $errors->kioskMedical->any() ? 'open' : '' }}>
                        <summary class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 text-slate-700 font-bold text-sm cursor-pointer hover:bg-slate-200 transition-colors">
                            Update Medical Record
                        </summary>
                        <form action="{{ route('kiosk.medical.update', $patient->id) }}" method="POST" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hypertension</label>
                                <select name="hypertension" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                                    @foreach(['None', 'Monitored', 'High Risk'] as $opt)
                                        <option value="{{ $opt }}" {{ old('hypertension', optional($medicalHistory)->hypertension ?: 'None') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Diabetes</label>
                                <select name="diabetes" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                                    @foreach(['None', 'Type 1', 'Type 2 (Controlled)', 'Type 2 (Uncontrolled)'] as $opt)
                                        <option value="{{ $opt }}" {{ old('diabetes', optional($medicalHistory)->diabetes ?: 'None') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Allergies</label><input type="text" name="allergies" value="{{ old('allergies', optional($medicalHistory)->allergies) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Drug Allergies</label><input type="text" name="drug_allergies" value="{{ old('drug_allergies', optional($medicalHistory)->drug_allergies) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                            <div class="md:col-span-2"><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Other Conditions</label><input type="text" name="others" value="{{ old('others', optional($medicalHistory)->others) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none"></div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pharmacist Code <span class="text-red-500">*</span></label>
                                <div class="flex gap-3 items-center">
                                    <input type="password" name="pharmacist_code" class="w-full md:w-1/2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                                    <button type="submit" class="px-5 py-2 rounded-xl bg-teal-600 text-white font-bold text-sm hover:bg-teal-700 transition-colors">Save Updates</button>
                                </div>
                            </div>
                        </form>
                    </details>
                </div>
            </div>

        </div>

        <div class="space-y-6">
            
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-red-200 bg-red-50 flex items-center gap-2 text-red-800">
                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="font-bold text-base">Health Alerts</h3>
                </div>
                <div class="p-6">
                    @if($riskFlags->isNotEmpty())
                        <div class="space-y-3">
                        @foreach($riskFlags as $flag)
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100 text-red-700 font-semibold text-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                {{ $flag }}
                            </div>
                        @endforeach
                        </div>
                    @else
                        <div class="flex items-center justify-center gap-2 p-4 rounded-xl bg-green-50 border border-green-100 text-green-700 font-semibold text-sm">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            No high-priority health alerts recorded
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-2 text-slate-800">
                    <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h3 class="font-bold text-base">Current Medications</h3>
                </div>
                <div class="p-6">
                    @forelse($patient->medications as $med)
                        <div class="flex items-center gap-4 p-3 mb-3 rounded-xl bg-slate-50 border border-slate-100 hover:shadow-sm transition-shadow">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center shadow-sm text-white shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>                            
                            <div>
                                <p class="font-bold text-slate-800 text-sm">{{ $med->name }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $med->dosage }}{{ $med->frequency ? ' · ' . $med->frequency : '' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 italic">No active medications recorded.</p>
                    @endforelse
                </div>
            </div>

            <div class="section-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="section-head px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-2 text-slate-800">
                    <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <h3 class="font-bold text-base">Latest Check-up</h3>
                </div>
                <div class="p-6">
                    @if($latestCheckup)
                        <p class="text-xs text-slate-500 mb-4 font-medium">
                            {{ \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') }}
                            @if($latestCheckup->report_source)
                                <span class="mx-1">·</span> {{ str_replace('_', ' ', ucfirst($latestCheckup->report_source)) }}
                            @endif
                        </p>

                        <div class="space-y-5">
                            <div>
                                <p class="text-[0.65rem] font-black uppercase tracking-wider text-slate-400 mb-2">Vitals</p>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                        <span class="text-xs font-bold text-slate-500">Blood Pressure</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-extrabold text-slate-800">{{ $latestCheckup->blood_pressure ?? 'N/A' }}</span>
                                            @php
                                                $bpParts = explode('/', $latestCheckup->blood_pressure ?? '');
                                                $sys = isset($bpParts[0]) ? (int)$bpParts[0] : 0;
                                                $bpBadge = $sys >= 140 ? ['High','rbadge-high'] : ($sys >= 120 ? ['Elevated','rbadge-borderline'] : ($sys > 0 ? ['Normal','rbadge-normal'] : ['N/A','rbadge-na']));
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $bpBadge[1] }}">{{ $bpBadge[0] }}</span>
                                        </div>
                                    </div>
                                    @if($latestCheckup->heart_rate)
                                    @php [$hl, $hc] = $readingBadge($latestCheckup->heart_rate, 'hr'); @endphp
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                        <span class="text-xs font-bold text-slate-500">Heart Rate</span>
                                        <div class="flex items-center gap-2"><span class="text-sm font-extrabold text-slate-800">{{ $latestCheckup->heart_rate }} bpm</span><span class="px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $hc }}">{{ $hl }}</span></div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <p class="text-[0.65rem] font-black uppercase tracking-wider text-slate-400 mb-2">Blood Glucose</p>
                                <div class="space-y-2">
                                    @php [$sugarL, $sugarC] = $readingBadge($latestCheckup->blood_sugar, 'sugar'); @endphp
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                        <span class="text-xs font-bold text-slate-500">Fasting Sugar</span>
                                        <div class="flex items-center gap-2"><span class="text-sm font-extrabold text-slate-800">{{ $latestCheckup->blood_sugar ? number_format($latestCheckup->blood_sugar,1).' mmol/L' : 'N/A' }}</span><span class="px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $sugarC }}">{{ $sugarL }}</span></div>
                                    </div>
                                    @if($latestCheckup->hba1c)
                                    @php [$hl, $hc] = $readingBadge($latestCheckup->hba1c, 'hba1c'); @endphp
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                        <span class="text-xs font-bold text-slate-500">HbA1c</span>
                                        <div class="flex items-center gap-2"><span class="text-sm font-extrabold text-slate-800">{{ number_format($latestCheckup->hba1c,1) }}%</span><span class="px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $hc }}">{{ $hl }}</span></div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <p class="text-[0.65rem] font-black uppercase tracking-wider text-slate-400 mb-2">Lipid Panel</p>
                                <div class="space-y-2">
                                    @php [$cl, $cc] = $readingBadge($latestCheckup->cholesterol, 'cholesterol'); @endphp
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                        <span class="text-xs font-bold text-slate-500">Total Chol.</span>
                                        <div class="flex items-center gap-2"><span class="text-sm font-extrabold text-slate-800">{{ $latestCheckup->cholesterol ? number_format($latestCheckup->cholesterol,1).' mmol/L' : 'N/A' }}</span><span class="px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $cc }}">{{ $cl }}</span></div>
                                    </div>
                                    @if($latestCheckup->ldl)
                                    @php [$ll, $lc] = $readingBadge($latestCheckup->ldl, 'ldl'); @endphp
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                        <span class="text-xs font-bold text-slate-500">LDL</span>
                                        <div class="flex items-center gap-2"><span class="text-sm font-extrabold text-slate-800">{{ number_format($latestCheckup->ldl,1) }} mmol/L</span><span class="px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $lc }}">{{ $ll }}</span></div>
                                    </div>
                                    @endif
                                    @if($latestCheckup->hdl)
                                    @php [$hdll, $hdlc] = $readingBadge($latestCheckup->hdl, 'hdl'); @endphp
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                        <span class="text-xs font-bold text-slate-500">HDL</span>
                                        <div class="flex items-center gap-2"><span class="text-sm font-extrabold text-slate-800">{{ number_format($latestCheckup->hdl,1) }} mmol/L</span><span class="px-2 py-0.5 rounded-full text-[0.65rem] font-bold {{ $hdlc }}">{{ $hdll }}</span></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($latestCheckup->notes)
                            <div class="mt-5 p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-600 leading-relaxed shadow-inner">
                                <strong class="text-slate-800 block mb-1">Clinical Notes:</strong>
                                {{ $latestCheckup->notes }}
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-slate-400 italic">No check-up records found.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</main>

<div class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-2.5 rounded-full bg-white border border-slate-200 shadow-xl shadow-slate-200 text-sm font-medium text-slate-600">
    <svg class="w-4 h-4 text-teal-500 animate-spin-slow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    Session expires in <span id="timer-count" class="font-extrabold text-teal-600 w-10 text-center">10:00</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Session Countdown Logic
(function () {
    const startedAt = {{ session('kiosk_auth_at') ?? 'null' }};
    if (!startedAt) return;

    const expiresAt = startedAt + 600;
    const timerEl   = document.getElementById('timer-count');
    const noticeEl  = document.getElementById('session-expire-notice');

    function update() {
        const remaining = expiresAt - Math.floor(Date.now() / 1000);
        if (remaining <= 0) { window.location.href = '{{ route("welcome") }}'; return; }
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        timerEl.textContent  = `${m}:${s}`;
        noticeEl.textContent = `${m}:${s}`;
        
        // Color shifts based on time
        if(remaining <= 60) {
            timerEl.classList.remove('text-teal-600', 'text-amber-600');
            timerEl.classList.add('text-red-600');
        } else if (remaining <= 120) {
            timerEl.classList.remove('text-teal-600', 'text-red-600');
            timerEl.classList.add('text-amber-600');
        }
    }
    update();
    setInterval(update, 1000);
})();

// DOM Moves & Interaction
document.addEventListener('DOMContentLoaded', function () {
    // Move latest checkup inside the create panel
    const latestCheckupSlot = document.getElementById('latest-checkup-slot');
    const latestCheckupCard = Array.from(document.querySelectorAll('.section-card')).find(function (card) {
        const heading = card.querySelector('.section-head');
        return heading && heading.textContent.includes('Latest Check-up');
    });

    if (latestCheckupSlot && latestCheckupCard) {
        latestCheckupSlot.appendChild(latestCheckupCard);
    }

    // Chart.js Implementations
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';

    const radarCanvas = document.getElementById('kioskRiskRadar');
    if (radarCanvas) {
        new Chart(radarCanvas.getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['BMI', 'Blood Sugar', 'Blood Pressure', 'Cholesterol', 'Med. Adherence'],
                datasets: [{
                    label: 'Health Profile',
                    data: [{{ $radarBmi }}, {{ $radarSugar }}, {{ $radarBp }}, {{ $radarCholest }}, {{ $radarAdherence }}],
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.2)',
                    pointBackgroundColor: '#0d9488',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true, max: 100, ticks: { display: false },
                        grid: { color: 'rgba(203, 213, 225, 0.4)' },
                        angleLines: { color: 'rgba(203, 213, 225, 0.4)' },
                        pointLabels: { font: { size: 11, weight: 'bold' }, color: '#475569' }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    const chartCanvas = document.getElementById('kioskHealthChart');
    if (chartCanvas) {
        new Chart(chartCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Blood Sugar (mmol/L)', data: {!! json_encode($sugarSeries) !!},
                        borderColor: '#0d9488', backgroundColor: 'rgba(13, 148, 136, 0.1)',
                        borderWidth: 3, pointBackgroundColor: '#0d9488',
                        pointRadius: 4, fill: true, tension: 0.4
                    },
                    {
                        label: 'Cholesterol (mmol/L)', data: {!! json_encode($cholesterolSeries) !!},
                        borderColor: '#06b6d4', backgroundColor: 'transparent',
                        borderWidth: 3, pointBackgroundColor: '#06b6d4',
                        pointRadius: 4, fill: false, tension: 0.4
                    },
                    {
                        label: 'LDL (mmol/L)', data: {!! json_encode($ldlSeries) !!},
                        borderColor: '#ef4444', backgroundColor: 'transparent',
                        borderWidth: 2, borderDash: [5, 3], pointBackgroundColor: '#ef4444',
                        pointRadius: 4, fill: false, tension: 0.4
                    },
                    {
                        label: 'HbA1c (%)', data: {!! json_encode($hba1cSeries) !!},
                        borderColor: '#f59e0b', backgroundColor: 'transparent',
                        borderWidth: 2, borderDash: [3, 3], pointBackgroundColor: '#f59e0b',
                        pointRadius: 4, fill: false, tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { weight: 'bold' } } },
                    tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8 }
                },
                scales: {
                    y: { grid: { color: 'rgba(241, 245, 249, 1)', drawBorder: false } },
                    x: { grid: { display: false, drawBorder: false } }
                }
            }
        });
    }
});
</script>
</body>
</html>