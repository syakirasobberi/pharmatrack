<x-app-layout>
    @php
        $latestCheckup   = $patient->healthCheckups->first();
        $medicalHistory  = $patient->medicalHistory;
        $medications     = $patient->medications;
        $activeMedCount  = $medications->count();
        $bmi             = (float) $patient->bmi;
        $patientName     = $patient->user->name ?: ('Patient #' . $patient->id);
        $patientEmail    = $patient->user->email ?: 'No email recorded';
        $assignedPharmacistName = $patient->pharmacist?->name ?? 'Unassigned';

        // ── Chart data ──────────────────────────────────────────────────────
        $chartData         = $patient->healthCheckups->sortBy('checkup_date')->values();
        $chartLabels       = $chartData->pluck('checkup_date')
                               ->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d M y'))
                               ->values();
        $sugarSeries       = $chartData->pluck('blood_sugar')->values();
        $cholesterolSeries = $chartData->pluck('cholesterol')->values();

        // ── BMI status ───────────────────────────────────────────────────────
        if ($bmi >= 30)       $bmiStatus = ['Obese',       'bg-red-100 text-red-700'];
        elseif ($bmi >= 25)   $bmiStatus = ['Overweight',  'bg-amber-100 text-amber-700'];
        elseif ($bmi < 18.5)  $bmiStatus = ['Underweight', 'bg-yellow-100 text-yellow-700'];
        else                  $bmiStatus = ['Healthy',     'bg-emerald-100 text-emerald-700'];

        // ── Rule-based alerts ────────────────────────────────────────────────
        // Each entry: ['message', 'severity']  severity: critical | review | stable
        $alerts = collect();

        // No recent check-up (> 90 days or none at all)
        if (!$latestCheckup) {
            $alerts->push(['No check-up recorded for this patient.', 'critical']);
        } elseif (\Carbon\Carbon::parse($latestCheckup->checkup_date)->diffInDays(now()) > 90) {
            $alerts->push(['No check-up in the last 90 days. Review recommended.', 'review']);
        }

        // No active medications
        if ($activeMedCount === 0) {
            $alerts->push(['No active medications recorded.', 'review']);
        }

        // Polypharmacy (≥ 5 medications)
        if ($activeMedCount >= 5) {
            $alerts->push(['Polypharmacy detected (' . $activeMedCount . ' medications). Medication review recommended.', 'review']);
        }

        // Drug allergies
        if (filled(optional($medicalHistory)->drug_allergies)) {
            $alerts->push(['Drug allergies recorded: ' . $medicalHistory->drug_allergies, 'critical']);
        }

        // General allergies
        if (filled(optional($medicalHistory)->allergies)) {
            $alerts->push(['General allergies recorded: ' . $medicalHistory->allergies, 'review']);
        }

        // High hypertension risk
        if (optional($medicalHistory)->hypertension === 'High Risk') {
            $alerts->push(['High blood pressure risk noted in medical history.', 'critical']);
        }

        // Diabetes
        if (filled(optional($medicalHistory)->diabetes) && optional($medicalHistory)->diabetes !== 'None') {
            $alerts->push(['Diabetes status: ' . $medicalHistory->diabetes, 'review']);
        }

        // Elevated blood sugar at latest check-up (> 7.0 mmol/L)
        if ($latestCheckup && (float)$latestCheckup->blood_sugar > 7.0) {
            $alerts->push(['Elevated blood sugar (' . $latestCheckup->blood_sugar . ' mmol/L) detected at last check-up.', 'critical']);
        }

        // Elevated cholesterol at latest check-up (> 5.2 mmol/L)
        if ($latestCheckup && (float)$latestCheckup->cholesterol > 5.2) {
            $alerts->push(['High cholesterol (' . $latestCheckup->cholesterol . ' mmol/L) detected at last check-up.', 'review']);
        }

        // If still empty, patient is stable
        if ($alerts->isEmpty()) {
            $alerts->push(['No high-priority alerts. Patient status is stable.', 'stable']);
        }

        // ── Radar normalisation (no ML, just scale to 0-100) ─────────────────
        $radarBmi        = $bmi > 0 ? min(round(($bmi / 40) * 100), 100) : 0;
        $radarSugar      = $latestCheckup ? min(round(((float)$latestCheckup->blood_sugar / 10) * 100), 100) : 0;
        $radarCholest    = $latestCheckup ? min(round(((float)$latestCheckup->cholesterol / 8)  * 100), 100) : 0;
        // blood_pressure stored as "120/80" — extract systolic
        $systolic        = 0;
        if ($latestCheckup && filled($latestCheckup->blood_pressure)) {
            $systolic = (int) explode('/', $latestCheckup->blood_pressure)[0];
        }
        $radarBp         = $systolic > 0 ? min(round(($systolic / 180) * 100), 100) : 0;
        // Medication adherence proxy: if medications exist and recent check-up exists → higher score
        $radarAdherence  = $activeMedCount > 0 && $latestCheckup ? min($activeMedCount * 15, 100) : 0;

        $hasRadarData    = ($radarBmi + $radarSugar + $radarCholest + $radarBp) > 0;

        // Latest medication update timestamp
        $latestMedDate   = $medications->sortByDesc('updated_at')->first()?->updated_at;
    @endphp

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{--  PAGE WRAPPER                                                        --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back link --}}
            <div>
                <a href="{{ route('pharmacist.patients.show', $patient->id) }}"
                   class="inline-flex items-center gap-1 text-slate-500 hover:text-blue-700 font-bold transition-colors text-sm">
                    &larr; Back to patient profile
                </a>
            </div>

            {{-- ── HEADER CARD ───────────────────────────────────────────── --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-6 md:px-8 text-white"
                     style="background: linear-gradient(90deg,#1d4ed8 0%,#0891b2 100%);">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">
                        <div class="flex items-center gap-4">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($patientName) }}&background=ffffff&color=1d4ed8&size=128&font-size=0.35&bold=true"
                                 alt="Patient avatar"
                                 class="w-20 h-20 rounded-full border-4 border-white/60 shadow-sm">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em]" style="color:#dbeafe;">
                                    Pharmacist Clinical View
                                </p>
                                <h1 class="text-3xl font-extrabold" style="color:#ffffff;">
                                    {{ $patientName }}
                                </h1>
                                <div class="flex flex-wrap gap-3 text-sm mt-2" style="color:#eff6ff;">
                                    <span>{{ $patient->gender }}</span>
                                    <span>{{ $patient->age }} years</span>
                                    <span>&bull;</span>
                                    <span>Assigned: {{ $assignedPharmacistName }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('pharmacist.patients.show', $patient->id) }}"
                               class="px-4 py-2 rounded-full bg-white text-blue-700 font-bold shadow-sm hover:bg-blue-50 transition-colors text-sm">
                                Full Profile
                            </a>
                            <a href="{{ route('pharmacist.patients.summary.download', $patient->id) }}"
                               class="px-4 py-2 rounded-full bg-white text-blue-700 font-bold shadow-sm hover:bg-blue-50 transition-colors text-sm">
                                Download Summary
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ── TOP STAT TILES ──────────────────────────────────────── --}}
                <div class="p-6 md:p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                        {{-- BMI --}}
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">BMI Status</p>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-3xl font-extrabold text-slate-800">
                                    {{ number_format($patient->bmi, 1) }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $bmiStatus[1] }}">
                                    {{ $bmiStatus[0] }}
                                </span>
                            </div>
                        </div>

                        {{-- Blood Pressure --}}
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Latest Blood Pressure</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-800">
                                {{ $latestCheckup?->blood_pressure ?? 'N/A' }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $latestCheckup
                                    ? \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y')
                                    : 'No check-up recorded yet' }}
                            </p>
                        </div>

                        {{-- Blood Sugar --}}
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Blood Sugar</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-800">
                                {{ $latestCheckup?->blood_sugar ?? 'N/A' }}
                                @if($latestCheckup?->blood_sugar)
                                    <span class="text-base font-medium text-slate-400">mmol/L</span>
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Cholesterol: {{ $latestCheckup?->cholesterol ?? 'N/A' }}
                                @if($latestCheckup?->cholesterol) mmol/L @endif
                            </p>
                        </div>

                        {{-- Active Medications --}}
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Active Medications</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-800">{{ $activeMedCount }}</p>
                            <p class="mt-1 text-sm text-slate-500">
                                @if($activeMedCount >= 5)
                                    <span class="text-amber-600 font-semibold">Polypharmacy — review recommended</span>
                                @elseif($activeMedCount === 0)
                                    No medications on record
                                @else
                                    {{ $activeMedCount }} active prescription(s)
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- ── MAIN CONTENT GRID ────────────────────────────────── --}}
                    {{-- Row 1: Clinical Snapshot (wide) + Alerts + Medication Preview --}}
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                        {{-- ── CLINICAL SNAPSHOT ─────────────────────────────── --}}
                        <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-extrabold text-slate-800">Clinical Snapshot</h2>
                                    <p class="text-sm text-slate-500 mt-0.5">
                                        Fast view of key health information for this patient.
                                    </p>
                                </div>
                                @if($latestCheckup)
                                    <span class="text-xs text-slate-400 whitespace-nowrap">
                                        Last updated
                                        {{ \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') }}
                                    </span>
                                @endif
                            </div>

                            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">

                                {{-- Body Metrics --}}
                                <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-blue-600 mb-3">
                                        Body Metrics
                                    </p>
                                    <dl class="space-y-2 text-sm text-slate-700">
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-slate-500">Height</dt>
                                            <dd class="font-bold">{{ $patient->height ?? 'N/A' }} cm</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-slate-500">Weight</dt>
                                            <dd class="font-bold">{{ $patient->weight ?? 'N/A' }} kg</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-slate-500">BMI</dt>
                                            <dd class="font-bold">{{ number_format($patient->bmi, 1) }}
                                                <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full {{ $bmiStatus[1] }}">
                                                    {{ $bmiStatus[0] }}
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                {{-- Latest Check-up --}}
                                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 mb-3">
                                        Latest Check-up
                                    </p>
                                    @if($latestCheckup)
                                        <dl class="space-y-2 text-sm text-slate-700">
                                            <div class="flex justify-between">
                                                <dt class="font-medium text-slate-500">Date</dt>
                                                <dd class="font-bold">{{ \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="font-medium text-slate-500">Blood Pressure</dt>
                                                <dd class="font-bold">{{ $latestCheckup->blood_pressure ?? 'N/A' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="font-medium text-slate-500">Blood Sugar</dt>
                                                <dd class="font-bold">{{ $latestCheckup->blood_sugar ?? 'N/A' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="font-medium text-slate-500">Cholesterol</dt>
                                                <dd class="font-bold">{{ $latestCheckup->cholesterol ?? 'N/A' }}</dd>
                                            </div>
                                        </dl>
                                    @else
                                        <p class="text-sm text-slate-400 italic">No check-up data recorded yet.</p>
                                    @endif
                                </div>

                                {{-- Medical History --}}
                                <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700 mb-3">
                                        Medical History
                                    </p>
                                    <dl class="space-y-2 text-sm text-slate-700">
                                        <div class="flex justify-between gap-2">
                                            <dt class="font-medium text-slate-500 shrink-0">Hypertension</dt>
                                            <dd class="font-bold text-right">{{ optional($medicalHistory)->hypertension ?: 'None' }}</dd>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <dt class="font-medium text-slate-500 shrink-0">Diabetes</dt>
                                            <dd class="font-bold text-right">{{ optional($medicalHistory)->diabetes ?: 'None' }}</dd>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <dt class="font-medium text-slate-500 shrink-0">Allergies</dt>
                                            <dd class="font-bold text-right">
                                                {{ filled(optional($medicalHistory)->allergies) ? $medicalHistory->allergies : 'None' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <dt class="font-medium text-slate-500 shrink-0">Drug Allergies</dt>
                                            <dd class="font-bold text-right">
                                                {{ filled(optional($medicalHistory)->drug_allergies) ? $medicalHistory->drug_allergies : 'None' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        {{-- ── RIGHT COLUMN: Alerts + Medication Preview ──────── --}}
                        <div class="space-y-5">

                            {{-- ALERTS --}}
                            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                                <div class="px-6 py-5 border-b border-slate-100">
                                    <h2 class="text-lg font-extrabold text-slate-800">Alerts</h2>
                                    <p class="text-xs text-slate-400 mt-0.5">Rule-based clinical flags</p>
                                </div>
                                <div class="p-5 space-y-2">
                                    @foreach($alerts as [$message, $severity])
                                        @php
                                            $alertStyle = match($severity) {
                                                'critical' => 'bg-red-50 border-red-100 text-red-700',
                                                'review'   => 'bg-amber-50 border-amber-100 text-amber-700',
                                                default    => 'bg-emerald-50 border-emerald-100 text-emerald-700',
                                            };
                                            $dot = match($severity) {
                                                'critical' => 'bg-red-400',
                                                'review'   => 'bg-amber-400',
                                                default    => 'bg-emerald-400',
                                            };
                                        @endphp
                                        <div class="rounded-xl border px-4 py-3 text-sm font-semibold flex items-start gap-2 {{ $alertStyle }}">
                                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $dot }}"></span>
                                            {{ $message }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- MEDICATION PREVIEW --}}
                            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                                <div class="px-6 py-5 border-b border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <h2 class="text-lg font-extrabold text-slate-800">Medications</h2>
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full
                                            {{ $activeMedCount > 0 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $activeMedCount }} active
                                        </span>
                                    </div>
                                    @if($latestMedDate)
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            Last updated {{ \Carbon\Carbon::parse($latestMedDate)->format('d M Y') }}
                                        </p>
                                    @endif
                                </div>
                                <div class="p-5 space-y-3">
                                    @forelse($medications->take(2) as $medication)
                                        <div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3">
                                            <p class="font-bold text-slate-800 text-sm">{{ $medication->name }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                {{ $medication->dosage }}
                                                {{ $medication->frequency ? ' · ' . $medication->frequency : '' }}
                                            </p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-400 italic">No active medications recorded.</p>
                                    @endforelse
                                    @if($activeMedCount > 2)
                                        <a href="{{ route('pharmacist.patients.show', $patient->id) }}"
                                           class="block text-center text-xs font-bold text-blue-600 hover:text-blue-800 pt-1">
                                            + {{ $activeMedCount - 2 }} more — view full profile
                                        </a>
                                    @endif
                                </div>
                            </div>

                        </div>{{-- end right column --}}
                    </div>

                    {{-- ── Row 2: Health Radar + Health Trend ─────────────────── --}}
                    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

                        {{-- HEALTH RADAR --}}
                        <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="px-6 py-5 border-b border-slate-100">
                                <h2 class="text-lg font-extrabold text-slate-800">Health Radar</h2>
                                <p class="text-sm text-slate-500 mt-0.5">
                                    Quick overview of current patient status.
                                </p>
                            </div>
                            <div class="p-6">
                                @if($hasRadarData)
                                    <div class="relative h-72">
                                        <canvas id="summaryRiskRadar"></canvas>
                                    </div>
                                @else
                                    <div class="h-72 flex flex-col items-center justify-center text-center gap-2">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                        <p class="text-sm font-semibold text-slate-400">Insufficient data for radar.</p>
                                        <p class="text-xs text-slate-400">Record a check-up to populate this chart.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- HEALTH TREND --}}
                        <div class="xl:col-span-3 rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="px-6 py-5 border-b border-slate-100">
                                <h2 class="text-lg font-extrabold text-slate-800">Health Trend Analytics</h2>
                                <p class="text-sm text-slate-500 mt-0.5">
                                    Blood sugar and cholesterol trends from recorded check-ups.
                                </p>
                            </div>
                            <div class="p-6">
                                @if($chartData->count() >= 2)
                                    <div class="relative h-72">
                                        <canvas id="summaryHealthTrend"></canvas>
                                    </div>
                                @else
                                    <div class="h-72 flex flex-col items-center justify-center text-center gap-2">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                                        </svg>
                                        <p class="text-sm font-semibold text-slate-400">No historical check-up data available.</p>
                                        <p class="text-xs text-slate-400">Complete at least 2 check-ups to view trends.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>{{-- end p-6 md:p-8 --}}
            </div>{{-- end outer card --}}

        </div>
    </div>

    {{-- ── SCRIPTS ────────────────────────────────────────────────────────── --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Radar chart ──────────────────────────────────────────────────
            const radarCanvas = document.getElementById('summaryRiskRadar');
            if (radarCanvas) {
                new Chart(radarCanvas.getContext('2d'), {
                    type: 'radar',
                    data: {
                        labels: ['BMI', 'Blood Sugar', 'Blood Pressure', 'Cholesterol', 'Med. Adherence'],
                        datasets: [{
                            label: 'Patient Health Profile',
                            data: [
                                {{ $radarBmi }},
                                {{ $radarSugar }},
                                {{ $radarBp }},
                                {{ $radarCholest }},
                                {{ $radarAdherence }}
                            ],
                            borderColor:          'rgb(8, 145, 178)',
                            backgroundColor:      'rgba(8, 145, 178, 0.16)',
                            pointBackgroundColor: 'rgb(37, 99, 235)',
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
                                grid:       { color: 'rgba(100,116,139,0.18)' },
                                angleLines: { color: 'rgba(100,116,139,0.18)' }
                            }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }

            // ── Trend chart ──────────────────────────────────────────────────
            const trendCanvas = document.getElementById('summaryHealthTrend');
            if (trendCanvas) {
                new Chart(trendCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($chartLabels) !!},
                        datasets: [
                            {
                                label: 'Blood Sugar (mmol/L)',
                                data:  {!! json_encode($sugarSeries) !!},
                                borderColor:          'rgb(37, 99, 235)',
                                backgroundColor:      'rgba(37, 99, 235, 0.10)',
                                borderWidth:          3,
                                pointBackgroundColor: 'rgb(37, 99, 235)',
                                fill:    true,
                                tension: 0.35
                            },
                            {
                                label: 'Cholesterol (mmol/L)',
                                data:  {!! json_encode($cholesterolSeries) !!},
                                borderColor:          'rgb(20, 184, 166)',
                                backgroundColor:      'transparent',
                                borderWidth:          3,
                                pointBackgroundColor: 'rgb(20, 184, 166)',
                                fill:    false,
                                tension: 0.35
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } },
                        scales:  { y: { beginAtZero: false } }
                    }
                });
            }
        });
    </script>
</x-app-layout>
