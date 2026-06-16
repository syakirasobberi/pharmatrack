@php
    $compact = $compact ?? false;

    $sourceLabels = [
        'klinik_kesihatan' => 'Klinik Kesihatan (KKM)',
        'private_clinic' => 'Private Clinic',
        'hospital' => 'Hospital',
        'private_lab' => 'Private Lab',
        'home_device' => 'Home Device / Self-measured',
    ];

    $valueWithUnit = function ($value, $unit = null, $decimals = null) {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        $display = is_numeric($value) && $decimals !== null
            ? number_format((float) $value, $decimals)
            : $value;

        return $unit ? "{$display} {$unit}" : $display;
    };

    $parseBloodPressure = function (?string $bloodPressure) {
        if (! $bloodPressure || ! preg_match('/(\d+)\s*\/\s*(\d+)/', $bloodPressure, $matches)) {
            return [null, null];
        }

        return [(int) $matches[1], (int) $matches[2]];
    };

    $metricStatusClass = function ($key, $value) use ($parseBloodPressure) {
        if ($value === null || $value === '') {
            return 'text-gray-800';
        }

        if ($key === 'blood_pressure') {
            [$systolic, $diastolic] = $parseBloodPressure($value);

            if ($systolic === null || $diastolic === null) {
                return 'text-gray-800';
            }

            return $systolic >= 130 || $diastolic >= 80 ? 'text-red-600' : 'text-emerald-700';
        }

        $numeric = (float) $value;

        return match ($key) {
            'heart_rate' => ($numeric < 60 || $numeric > 100) ? 'text-amber-700' : 'text-emerald-700',
            'haemoglobin' => ($numeric < 12.0 || $numeric > 16.0) ? 'text-amber-700' : 'text-emerald-700',
            'blood_sugar' => ($numeric < 3.9 || $numeric > 6.0) ? 'text-red-600' : 'text-emerald-700',
            'hba1c' => $numeric >= 5.7 ? 'text-red-600' : 'text-emerald-700',
            'cholesterol' => $numeric >= 5.2 ? 'text-amber-700' : 'text-emerald-700',
            'ldl' => $numeric >= 2.6 ? 'text-amber-700' : 'text-emerald-700',
            'hdl' => $numeric <= 1.3 ? 'text-amber-700' : 'text-emerald-700',
            'alkaline_phosphatase' => ($numeric < 38 || $numeric > 124) ? 'text-amber-700' : 'text-emerald-700',
            'aspartate_transaminase' => $numeric >= 34 ? 'text-amber-700' : 'text-emerald-700',
            'alanine_transaminase' => ($numeric < 10 || $numeric > 49) ? 'text-amber-700' : 'text-emerald-700',
            'gamma_glutamyl_transferase' => $numeric >= 38 ? 'text-amber-700' : 'text-emerald-700',
            'sodium' => ($numeric < 135 || $numeric > 145) ? 'text-amber-700' : 'text-emerald-700',
            'renal_glucose' => ($numeric < 3.9 || $numeric > 6.0) ? 'text-amber-700' : 'text-emerald-700',
            default => 'text-gray-800',
        };
    };

    $hasMonitorFlag = function ($checkup) use ($parseBloodPressure) {
        [$systolic, $diastolic] = $parseBloodPressure($checkup->blood_pressure);

        return ($systolic !== null && ($systolic >= 130 || $diastolic >= 80))
            || ($checkup->heart_rate !== null && ($checkup->heart_rate < 60 || $checkup->heart_rate > 100))
            || ($checkup->haemoglobin !== null && ($checkup->haemoglobin < 12.0 || $checkup->haemoglobin > 16.0))
            || ($checkup->blood_sugar !== null && ($checkup->blood_sugar < 3.9 || $checkup->blood_sugar > 6.0))
            || ($checkup->hba1c !== null && $checkup->hba1c >= 5.7)
            || ($checkup->cholesterol !== null && $checkup->cholesterol >= 5.2)
            || ($checkup->ldl !== null && $checkup->ldl >= 2.6)
            || ($checkup->hdl !== null && $checkup->hdl <= 1.3)
            || ($checkup->alkaline_phosphatase !== null && ($checkup->alkaline_phosphatase < 38 || $checkup->alkaline_phosphatase > 124))
            || ($checkup->aspartate_transaminase !== null && $checkup->aspartate_transaminase >= 34)
            || ($checkup->alanine_transaminase !== null && ($checkup->alanine_transaminase < 10 || $checkup->alanine_transaminase > 49))
            || ($checkup->gamma_glutamyl_transferase !== null && $checkup->gamma_glutamyl_transferase >= 38)
            || ($checkup->sodium !== null && ($checkup->sodium < 135 || $checkup->sodium > 145))
            || ($checkup->renal_glucose !== null && ($checkup->renal_glucose < 3.9 || $checkup->renal_glucose > 6.0));
    };

    $sections = [
        'Vitals' => [
            ['key' => 'blood_pressure', 'label' => 'Blood Pressure', 'value' => $checkup->blood_pressure ?: null, 'unit' => 'mmHg', 'decimals' => null],
            ['key' => 'heart_rate', 'label' => 'Heart Rate', 'value' => $checkup->heart_rate, 'unit' => 'bpm', 'decimals' => 0],
            ['key' => 'haemoglobin', 'label' => 'Haemoglobin', 'value' => $checkup->haemoglobin, 'unit' => 'g/dL', 'decimals' => 2],
        ],
        'Blood Glucose' => [
            ['key' => 'blood_sugar', 'label' => 'Fasting Blood Sugar', 'value' => $checkup->blood_sugar, 'unit' => 'mmol/L', 'decimals' => 2],
            ['key' => 'hba1c', 'label' => 'HbA1c', 'value' => $checkup->hba1c, 'unit' => '%', 'decimals' => 1],
        ],
        'Liver Function Test' => [
            ['key' => 'albumin_globulin_ratio', 'label' => 'Albumin-Globulin Ratio', 'value' => $checkup->albumin_globulin_ratio, 'unit' => null, 'decimals' => 2],
            ['key' => 'alkaline_phosphatase', 'label' => 'Alkaline Phosphatase', 'value' => $checkup->alkaline_phosphatase, 'unit' => 'U/L', 'decimals' => 2],
            ['key' => 'aspartate_transaminase', 'label' => 'Aspartate Transaminase', 'value' => $checkup->aspartate_transaminase, 'unit' => 'U/L', 'decimals' => 2],
            ['key' => 'alanine_transaminase', 'label' => 'Alanine Transaminase', 'value' => $checkup->alanine_transaminase, 'unit' => 'U/L', 'decimals' => 2],
            ['key' => 'gamma_glutamyl_transferase', 'label' => 'Gamma Glutamyl Transferase', 'value' => $checkup->gamma_glutamyl_transferase, 'unit' => 'U/L', 'decimals' => 2],
        ],
        'Renal Function Profile' => [
            ['key' => 'sodium', 'label' => 'Sodium', 'value' => $checkup->sodium, 'unit' => 'mmol/L', 'decimals' => 2],
            ['key' => 'renal_glucose', 'label' => 'Glucose', 'value' => $checkup->renal_glucose, 'unit' => 'mmol/L', 'decimals' => 2],
        ],
        'Lipid Panel' => [
            ['key' => 'cholesterol', 'label' => 'Total Cholesterol', 'value' => $checkup->cholesterol, 'unit' => 'mmol/L', 'decimals' => 2],
            ['key' => 'ldl', 'label' => 'LDL', 'value' => $checkup->ldl, 'unit' => 'mmol/L', 'decimals' => 2],
            ['key' => 'hdl', 'label' => 'HDL', 'value' => $checkup->hdl, 'unit' => 'mmol/L', 'decimals' => 2],
        ],
    ];

    $hasMonitorFlag = $hasMonitorFlag($checkup);
@endphp

<article class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Health Checkup</p>
            <h4 class="mt-1 text-base font-extrabold text-gray-900">
                {{ \Carbon\Carbon::parse($checkup->checkup_date)->format('d M Y') }}
            </h4>
            <p class="mt-1 text-xs font-semibold text-gray-500">
                Source: {{ $sourceLabels[$checkup->report_source] ?? ($checkup->report_source ?: 'Not recorded') }}
            </p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-bold {{ $hasMonitorFlag ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
            {{ $hasMonitorFlag ? 'Monitor' : 'Normal' }}
        </span>
    </div>

    <div class="space-y-5 p-5">
        @foreach($sections as $sectionTitle => $metrics)
            <section>
                <h5 class="text-xs font-extrabold uppercase tracking-wide text-gray-500">{{ $sectionTitle }}</h5>
                <div class="mt-3 grid grid-cols-1 gap-3 {{ $compact ? 'sm:grid-cols-2' : 'sm:grid-cols-2 lg:grid-cols-3' }}">
                    @foreach($metrics as $metric)
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold text-gray-500">{{ $metric['label'] }}</p>
                            <p class="mt-1 text-sm font-extrabold {{ $metricStatusClass($metric['key'], $metric['value']) }}">
                                {{ $valueWithUnit($metric['value'], $metric['unit'], $metric['decimals']) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if(filled($checkup->notes))
            <section class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
                <h5 class="text-xs font-extrabold uppercase tracking-wide text-indigo-700">Pharmacist Notes</h5>
                <p class="mt-2 text-sm font-medium leading-6 text-indigo-950">{{ $checkup->notes }}</p>
            </section>
        @endif
    </div>
</article>
