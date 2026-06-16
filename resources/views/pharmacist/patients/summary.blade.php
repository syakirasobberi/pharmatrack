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
        $hba1cSeries       = $chartData->pluck('hba1c')->values();
        $ldlSeries         = $chartData->pluck('ldl')->values();
        $previousCheckup   = $patient->healthCheckups->skip(1)->first();

        $parseSystolic = function (?string $bloodPressure) {
            if (! $bloodPressure || ! preg_match('/(\d+)/', $bloodPressure, $matches)) {
                return null;
            }

            return (float) $matches[1];
        };

        $riskScore = function ($key, $value) {
            if ($value === null || $value === '') {
                return null;
            }

            $value = (float) $value;

            return match ($key) {
                'blood_pressure' => min(round(($value / 180) * 100), 100),
                'blood_sugar' => min(round(($value / 10) * 100), 100),
                'cholesterol' => min(round(($value / 8) * 100), 100),
                'haemoglobin' => min(round((abs($value - 14) / 8) * 100), 100),
                'hba1c' => min(round(($value / 10) * 100), 100),
                'ldl' => min(round(($value / 5) * 100), 100),
                'hdl' => min(round((max(0, 2 - $value) / 2) * 100), 100),
                'albumin_globulin_ratio' => min(round((abs($value - 1.5) / 2) * 100), 100),
                'alkaline_phosphatase' => min(round(($value / 200) * 100), 100),
                'aspartate_transaminase' => min(round(($value / 80) * 100), 100),
                'alanine_transaminase' => min(round(($value / 90) * 100), 100),
                'gamma_glutamyl_transferase' => min(round(($value / 100) * 100), 100),
                'sodium' => min(round((abs($value - 140) / 20) * 100), 100),
                'renal_glucose' => min(round(($value / 10) * 100), 100),
                default => null,
            };
        };

        $formatMetric = function ($value, $unit = null, $decimals = 1) {
            if ($value === null || $value === '') {
                return 'N/A';
            }

            $display = is_numeric($value) ? number_format((float) $value, $decimals) : $value;

            return $unit ? "{$display} {$unit}" : $display;
        };

        $comparisonMetrics = collect([
            ['key' => 'blood_pressure', 'label' => 'Blood Pressure', 'unit' => 'mmHg systolic', 'previous' => $parseSystolic($previousCheckup?->blood_pressure), 'latest' => $parseSystolic($latestCheckup?->blood_pressure), 'care' => 'blood pressure control, salt intake, stress, and follow-up BP checks'],
            ['key' => 'haemoglobin', 'label' => 'Haemoglobin', 'unit' => 'g/dL', 'previous' => $previousCheckup?->haemoglobin, 'latest' => $latestCheckup?->haemoglobin, 'care' => 'haemoglobin status and follow-up screening'],
            ['key' => 'blood_sugar', 'label' => 'Blood Sugar', 'unit' => 'mmol/L', 'previous' => $previousCheckup?->blood_sugar, 'latest' => $latestCheckup?->blood_sugar, 'care' => 'sugar intake, meal timing, and diabetes monitoring'],
            ['key' => 'cholesterol', 'label' => 'Cholesterol', 'unit' => 'mmol/L', 'previous' => $previousCheckup?->cholesterol, 'latest' => $latestCheckup?->cholesterol, 'care' => 'fat intake, exercise, and lipid monitoring'],
            ['key' => 'hba1c', 'label' => 'HbA1c', 'unit' => '%', 'previous' => $previousCheckup?->hba1c, 'latest' => $latestCheckup?->hba1c, 'care' => 'long-term blood sugar control'],
            ['key' => 'albumin_globulin_ratio', 'label' => 'A/G Ratio', 'unit' => null, 'previous' => $previousCheckup?->albumin_globulin_ratio, 'latest' => $latestCheckup?->albumin_globulin_ratio, 'care' => 'liver function profile follow-up'],
            ['key' => 'alkaline_phosphatase', 'label' => 'ALP', 'unit' => 'U/L', 'previous' => $previousCheckup?->alkaline_phosphatase, 'latest' => $latestCheckup?->alkaline_phosphatase, 'care' => 'liver enzyme monitoring'],
            ['key' => 'aspartate_transaminase', 'label' => 'AST', 'unit' => 'U/L', 'previous' => $previousCheckup?->aspartate_transaminase, 'latest' => $latestCheckup?->aspartate_transaminase, 'care' => 'liver enzyme monitoring'],
            ['key' => 'alanine_transaminase', 'label' => 'ALT', 'unit' => 'U/L', 'previous' => $previousCheckup?->alanine_transaminase, 'latest' => $latestCheckup?->alanine_transaminase, 'care' => 'liver enzyme monitoring'],
            ['key' => 'gamma_glutamyl_transferase', 'label' => 'GGT', 'unit' => 'U/L', 'previous' => $previousCheckup?->gamma_glutamyl_transferase, 'latest' => $latestCheckup?->gamma_glutamyl_transferase, 'care' => 'liver enzyme monitoring'],
            ['key' => 'sodium', 'label' => 'Sodium', 'unit' => 'mmol/L', 'previous' => $previousCheckup?->sodium, 'latest' => $latestCheckup?->sodium, 'care' => 'renal function profile follow-up'],
            ['key' => 'renal_glucose', 'label' => 'Renal Glucose', 'unit' => 'mmol/L', 'previous' => $previousCheckup?->renal_glucose, 'latest' => $latestCheckup?->renal_glucose, 'care' => 'renal glucose pattern monitoring'],
            ['key' => 'ldl', 'label' => 'LDL', 'unit' => 'mmol/L', 'previous' => $previousCheckup?->ldl, 'latest' => $latestCheckup?->ldl, 'care' => 'heart health and high LDL cholesterol'],
            ['key' => 'hdl', 'label' => 'HDL', 'unit' => 'mmol/L', 'previous' => $previousCheckup?->hdl, 'latest' => $latestCheckup?->hdl, 'care' => 'healthy cholesterol balance and physical activity'],
        ])->filter(fn ($metric) => $metric['previous'] !== null && $metric['latest'] !== null)
          ->map(function ($metric) use ($riskScore, $formatMetric) {
              $previousScore = $riskScore($metric['key'], $metric['previous']);
              $latestScore = $riskScore($metric['key'], $metric['latest']);
              $scoreChange = $latestScore - $previousScore;

              $metric['previous_score'] = $previousScore;
              $metric['latest_score'] = $latestScore;
              $metric['previous_display'] = $formatMetric($metric['previous'], $metric['unit']);
              $metric['latest_display'] = $formatMetric($metric['latest'], $metric['unit']);
              $metric['status'] = abs($scoreChange) <= 3 ? 'stable' : ($scoreChange < 0 ? 'improved' : 'worsened');
              $metric['change_label'] = abs($scoreChange) <= 3 ? 'About the same' : ($scoreChange < 0 ? 'Improved' : 'Worsened');

              return $metric;
          })->values();

        $comparisonLabels = $comparisonMetrics->pluck('label')->values();
        $comparisonPreviousScores = $comparisonMetrics->pluck('previous_score')->values();
        $comparisonLatestScores = $comparisonMetrics->pluck('latest_score')->values();
        $comparisonRawValues = $comparisonMetrics->map(fn ($metric) => [
            'previous' => $metric['previous_display'],
            'latest' => $metric['latest_display'],
            'status' => $metric['change_label'],
        ])->values();
        $hasComparisonData = $comparisonMetrics->isNotEmpty();
        $improvedMetrics = $comparisonMetrics->where('status', 'improved')->values();
        $worsenedMetrics = $comparisonMetrics->where('status', 'worsened')->values();
        $stableMetrics = $comparisonMetrics->where('status', 'stable')->values();

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

        // Blood sugar outside latest normal range (3.9-6.0 mmol/L)
        if ($latestCheckup && is_numeric($latestCheckup->blood_sugar) && ((float)$latestCheckup->blood_sugar < 3.9 || (float)$latestCheckup->blood_sugar > 6.0)) {
            $alerts->push(['Blood sugar outside normal range (' . $latestCheckup->blood_sugar . ' mmol/L) detected at last check-up.', 'critical']);
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

        $hasCriticalAlert = $alerts->contains(fn ($alert) => $alert[1] === 'critical');
        $hasReviewAlert = $alerts->contains(fn ($alert) => $alert[1] === 'review');

        if ($hasCriticalAlert || $worsenedMetrics->count() >= 2) {
            $healthStatus = 'Needs close monitoring';
            $healthStatusClass = 'bg-red-100 text-red-700';
        } elseif ($hasReviewAlert || $worsenedMetrics->isNotEmpty()) {
            $healthStatus = 'Needs monitoring';
            $healthStatusClass = 'bg-amber-100 text-amber-700';
        } else {
            $healthStatus = 'Stable';
            $healthStatusClass = 'bg-emerald-100 text-emerald-700';
        }

        $healthSummaryText = match (true) {
            $hasCriticalAlert => 'The patient has high-priority risk factors that should be monitored closely and reviewed with the pharmacist.',
            $worsenedMetrics->count() >= 2 => 'Several readings are trending worse compared with the previous check-up, so follow-up monitoring is recommended.',
            $worsenedMetrics->isNotEmpty() => 'Most readings are acceptable, but at least one area has worsened and should be watched.',
            $improvedMetrics->isNotEmpty() => 'The latest check-up shows improvement in some readings. Continue monitoring to maintain the progress.',
            default => 'The available readings look stable. Continue routine check-ups and medication review as planned.',
        };

        $carePoints = collect();

        if (! $latestCheckup) {
            $carePoints->push('Complete a health check-up so current readings can be reviewed.');
        } elseif (\Carbon\Carbon::parse($latestCheckup->checkup_date)->diffInDays(now()) > 90) {
            $carePoints->push('Schedule a follow-up check-up because the latest record is older than 90 days.');
        }

        foreach ($worsenedMetrics as $metric) {
            $carePoints->push('Watch ' . strtolower($metric['care']) . '.');
        }

        if ($latestCheckup && is_numeric($latestCheckup->blood_sugar) && ((float) $latestCheckup->blood_sugar < 3.9 || (float) $latestCheckup->blood_sugar > 6.0)) {
            $carePoints->push('Review blood sugar pattern, food intake, and diabetes risk.');
        }

        if ($latestCheckup && (float) $latestCheckup->cholesterol >= 5.2) {
            $carePoints->push('Review cholesterol control, diet, exercise, and lipid follow-up.');
        }

        if ($latestCheckup && $systolic >= 130) {
            $carePoints->push('Monitor blood pressure and counsel on salt intake, stress, and follow-up readings.');
        }

        if ($activeMedCount >= 5) {
            $carePoints->push('Review medication list for polypharmacy and adherence issues.');
        } elseif ($activeMedCount === 0) {
            $carePoints->push('Confirm whether the patient is taking any medication not yet recorded.');
        }

        if (filled(optional($medicalHistory)->drug_allergies) || filled(optional($medicalHistory)->allergies)) {
            $carePoints->push('Check allergy history before recommending or dispensing medication.');
        }

        if ($carePoints->isEmpty()) {
            $carePoints->push('Continue routine check-ups, medication adherence, balanced diet, and regular activity.');
        }

        $carePoints = $carePoints->unique()->take(5)->values();
        // Medication adherence proxy: if medications exist and recent check-up exists → higher score
        $radarAdherence  = $activeMedCount > 0 && $latestCheckup ? min($activeMedCount * 15, 100) : 0;

        $hasRadarData    = ($radarBmi + $radarSugar + $radarCholest + $radarBp) > 0;

        // Latest medication update timestamp
        $latestMedDate   = $medications->sortByDesc('updated_at')->first()?->updated_at;

        $aiSuggestionText = $latestCheckup?->ai_suggestion;
        $aiSuggestionStyles = [
            'food' => 'border-emerald-100 bg-emerald-50 text-emerald-900',
            'exercise' => 'border-blue-100 bg-blue-50 text-blue-900',
            'follow-up' => 'border-amber-100 bg-amber-50 text-amber-900',
            'medication review' => 'border-purple-100 bg-purple-50 text-purple-900',
        ];
        $aiSuggestionCards = collect();

        if (filled($aiSuggestionText)) {
            $aiSuggestionCards = collect(preg_split('/\R+|(?=\d+\.\s*[A-Za-z])/', $aiSuggestionText) ?: [])
                ->map(fn ($line) => trim(preg_replace('/^\d+\.\s*/', '', str_replace('*', '', $line))))
                ->filter()
                ->map(function ($line) use ($aiSuggestionStyles) {
                    [$title, $body] = str_contains($line, ':')
                        ? array_map('trim', explode(':', $line, 2))
                        : ['Recommendation', $line];

                    $key = strtolower($title);

                    return [
                        'title' => $title,
                        'body' => $body ?: $line,
                        'style' => $aiSuggestionStyles[$key] ?? 'border-indigo-100 bg-white text-indigo-900',
                    ];
                })
                ->values();
        }
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
                                                <dt class="font-medium text-slate-500">Haemoglobin</dt>
                                                <dd class="font-bold">{{ $latestCheckup->haemoglobin ?? 'N/A' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="font-medium text-slate-500">Blood Sugar</dt>
                                                <dd class="font-bold">{{ $latestCheckup->blood_sugar ?? 'N/A' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="font-medium text-slate-500">Sodium</dt>
                                                <dd class="font-bold">{{ $latestCheckup->sodium ?? 'N/A' }}</dd>
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

                    {{-- Patient health summary --}}
                    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="px-6 py-5 border-b border-slate-100">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 class="text-lg font-extrabold text-slate-800">Patient Health Summary</h2>
                                        <p class="text-sm text-slate-500 mt-0.5">Current status, care priorities, and API-generated health suggestions.</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $healthStatusClass }}">
                                            {{ $healthStatus }}
                                        </span>
                                        <button type="button" id="generateOverallSummary" class="rounded-full bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700">
                                            {{ $aiSuggestionCards->isNotEmpty() ? 'Regenerate API Suggestion' : 'Generate API Suggestion' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 p-6">
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Summary</p>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-800">{{ $healthSummaryText }}</p>
                                </div>

                                <div class="rounded-2xl bg-white border border-slate-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Reading Direction</p>
                                    <div class="mt-3 grid grid-cols-3 gap-2">
                                        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3 text-center">
                                            <p class="text-2xl font-extrabold text-emerald-700">{{ $improvedMetrics->count() }}</p>
                                            <p class="text-[11px] font-bold uppercase text-emerald-700">Better</p>
                                        </div>
                                        <div class="rounded-2xl bg-red-50 border border-red-100 p-3 text-center">
                                            <p class="text-2xl font-extrabold text-red-700">{{ $worsenedMetrics->count() }}</p>
                                            <p class="text-[11px] font-bold uppercase text-red-700">Worse</p>
                                        </div>
                                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 text-center">
                                            <p class="text-2xl font-extrabold text-slate-700">{{ $stableMetrics->count() }}</p>
                                            <p class="text-[11px] font-bold uppercase text-slate-500">Same</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-white border border-slate-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Need to Take Care</p>
                                    <ul class="mt-3 space-y-2">
                                        @foreach($carePoints as $point)
                                            <li class="flex items-start gap-2 rounded-xl bg-amber-50 border border-amber-100 px-3 py-2 text-sm font-semibold leading-5 text-amber-800">
                                                <span class="mt-1 h-2 w-2 rounded-full bg-amber-400 shrink-0"></span>
                                                {{ $point }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div id="apiOverallSummary" class="xl:col-span-3 {{ $aiSuggestionCards->isNotEmpty() ? '' : 'hidden' }} rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-700">AI Health Insights</p>
                                    <div id="apiOverallSummaryText" class="mt-3">
                                        @if($aiSuggestionCards->isNotEmpty())
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                @foreach($aiSuggestionCards as $card)
                                                    <div class="rounded-xl border {{ $card['style'] }} p-4">
                                                        <p class="text-xs font-extrabold uppercase tracking-wide">{{ $card['title'] }}</p>
                                                        <p class="mt-1 text-sm font-medium leading-6">{{ $card['body'] }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <p class="mt-3 text-xs font-semibold text-indigo-700">
                                        Medication notes are for pharmacist review only. The system does not automatically prescribe medication.
                                    </p>
                                </div>
                            </div>
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
                                <h2 class="text-lg font-extrabold text-slate-800">Key Health Trend</h2>
                                <p class="text-sm text-slate-500 mt-0.5">
                                    High-concern readings from recorded check-ups: blood sugar, cholesterol, HbA1c, and LDL when available.
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
            const generateSummaryButton = document.getElementById('generateOverallSummary');
            const apiSummaryBox = document.getElementById('apiOverallSummary');
            const apiSummaryText = document.getElementById('apiOverallSummaryText');

            if (generateSummaryButton && apiSummaryBox && apiSummaryText) {
                const renderSuggestionCards = (suggestion) => {
                    const styles = {
                        food: 'border-emerald-100 bg-emerald-50 text-emerald-900',
                        exercise: 'border-blue-100 bg-blue-50 text-blue-900',
                        'follow-up': 'border-amber-100 bg-amber-50 text-amber-900',
                        'medication review': 'border-purple-100 bg-purple-50 text-purple-900',
                    };

                    const escapeHtml = (value) => value
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');

                    const cards = suggestion
                        .replace(/\*/g, '')
                        .split(/\n|(?=\d+\.\s*[A-Za-z])/)
                        .map(line => line.trim())
                        .filter(Boolean)
                        .map(line => {
                            const cleaned = line.replace(/^\d+\.\s*/, '');
                            const [rawTitle, ...rest] = cleaned.split(':');
                            const title = rawTitle.trim();
                            const body = rest.join(':').trim() || cleaned;
                            const style = styles[title.toLowerCase()] || 'border-indigo-100 bg-white text-indigo-900';

                            return `<div class="rounded-xl border ${style} p-4">
                                <p class="text-xs font-extrabold uppercase tracking-wide">${escapeHtml(title)}</p>
                                <p class="mt-1 text-sm font-medium leading-6">${escapeHtml(body)}</p>
                            </div>`;
                        })
                        .join('');

                    apiSummaryText.innerHTML = `<div class="grid grid-cols-1 md:grid-cols-2 gap-3">${cards}</div>`;
                };

                generateSummaryButton.addEventListener('click', async function () {
                    generateSummaryButton.disabled = true;
                    generateSummaryButton.textContent = 'Generating...';
                    apiSummaryBox.classList.remove('hidden');
                    apiSummaryText.innerHTML = '<span class="animate-pulse text-indigo-600 font-bold">Analyzing latest check-up metrics...</span>';

                    try {
                        const response = await fetch('{{ route('pharmacist.patients.aiSummary', $patient->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({})
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Unable to generate suggestion.');
                        }

                        renderSuggestionCards(data.suggestion);
                    } catch (error) {
                        apiSummaryText.textContent = error.message || 'Unable to generate API suggestion right now.';
                    } finally {
                        generateSummaryButton.disabled = false;
                        generateSummaryButton.textContent = 'Regenerate API Suggestion';
                    }
                });
            }

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
                            },
                            {
                                label: 'HbA1c (%)',
                                data:  {!! json_encode($hba1cSeries) !!},
                                borderColor:          'rgb(147, 51, 234)',
                                backgroundColor:      'transparent',
                                borderWidth:          3,
                                pointBackgroundColor: 'rgb(147, 51, 234)',
                                fill:    false,
                                tension: 0.35
                            },
                            {
                                label: 'LDL (mmol/L)',
                                data:  {!! json_encode($ldlSeries) !!},
                                borderColor:          'rgb(245, 158, 11)',
                                backgroundColor:      'transparent',
                                borderWidth:          3,
                                pointBackgroundColor: 'rgb(245, 158, 11)',
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
