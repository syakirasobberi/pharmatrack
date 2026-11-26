@php
    $compact = $compact ?? false;
    // collapsible: render as <details> (used for older check-ups); open: start expanded.
    $collapsible = $collapsible ?? false;
    $open = $open ?? true;

    $sourceLabels = [
        'klinik_kesihatan' => 'Klinik Kesihatan (KKM)',
        'private_clinic' => __('Private Clinic'),
        'hospital' => 'Hospital',
        'private_lab' => __('Private Lab'),
        'home_device' => __('Home Device / Self-measured'),
    ];

    $valueWithUnit = function ($value, $unit = null, $decimals = null) {
        if ($value === null || $value === '') {
            return __('N/A');
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
        __('Vitals') => [
            ['key' => 'blood_pressure', 'label' => __('Blood Pressure'), 'value' => $checkup->blood_pressure ?: null, 'unit' => 'mmHg', 'decimals' => null],
            ['key' => 'heart_rate', 'label' => __('Heart Rate'), 'value' => $checkup->heart_rate, 'unit' => 'bpm', 'decimals' => 0],
            ['key' => 'haemoglobin', 'label' => __('Haemoglobin'), 'value' => $checkup->haemoglobin, 'unit' => 'g/dL', 'decimals' => 2],
        ],
        __('Blood Glucose') => [
            ['key' => 'blood_sugar', 'label' => __('Fasting Blood Sugar'), 'value' => $checkup->blood_sugar, 'unit' => 'mmol/L', 'decimals' => 2],
            ['key' => 'hba1c', 'label' => 'HbA1c', 'value' => $checkup->hba1c, 'unit' => '%', 'decimals' => 1],
        ],
        __('Liver Function Test') => [
            ['key' => 'albumin_globulin_ratio', 'label' => __('Albumin-Globulin Ratio'), 'value' => $checkup->albumin_globulin_ratio, 'unit' => null, 'decimals' => 2],
            ['key' => 'alkaline_phosphatase', 'label' => 'Alkaline Phosphatase', 'value' => $checkup->alkaline_phosphatase, 'unit' => 'U/L', 'decimals' => 2],
            ['key' => 'aspartate_transaminase', 'label' => 'Aspartate Transaminase', 'value' => $checkup->aspartate_transaminase, 'unit' => 'U/L', 'decimals' => 2],
            ['key' => 'alanine_transaminase', 'label' => 'Alanine Transaminase', 'value' => $checkup->alanine_transaminase, 'unit' => 'U/L', 'decimals' => 2],
            ['key' => 'gamma_glutamyl_transferase', 'label' => 'Gamma Glutamyl Transferase', 'value' => $checkup->gamma_glutamyl_transferase, 'unit' => 'U/L', 'decimals' => 2],
        ],
        __('Renal Function Profile') => [
            ['key' => 'sodium', 'label' => __('Sodium'), 'value' => $checkup->sodium, 'unit' => 'mmol/L', 'decimals' => 2],
            ['key' => 'renal_glucose', 'label' => __('Glucose'), 'value' => $checkup->renal_glucose, 'unit' => 'mmol/L', 'decimals' => 2],
        ],
        __('Lipid Panel') => [
            ['key' => 'cholesterol', 'label' => __('Total Cholesterol'), 'value' => $checkup->cholesterol, 'unit' => 'mmol/L', 'decimals' => 2],
            ['key' => 'ldl', 'label' => 'LDL', 'value' => $checkup->ldl, 'unit' => 'mmol/L', 'decimals' => 2],
            ['key' => 'hdl', 'label' => 'HDL', 'value' => $checkup->hdl, 'unit' => 'mmol/L', 'decimals' => 2],
        ],
    ];

    $hasMonitorFlag = $hasMonitorFlag($checkup);

    // Only show tests that were actually done; skip whole sections with nothing recorded.
    $sections = collect($sections)
        ->map(fn ($metrics) => array_values(array_filter($metrics, fn ($m) => $m['value'] !== null && $m['value'] !== '')))
        ->filter(fn ($metrics) => count($metrics) > 0);

    // Say the result in words as well as colour.
    $lowerBounds = ['heart_rate' => 60, 'haemoglobin' => 12.0, 'blood_sugar' => 3.9, 'alkaline_phosphatase' => 38, 'alanine_transaminase' => 10, 'sodium' => 135, 'renal_glucose' => 3.9];
    $metricStatusLabel = function ($key, $value, $class) use ($lowerBounds) {
        if (str_contains($class, 'emerald')) {
            return 'Normal';
        }
        if (! str_contains($class, 'red') && ! str_contains($class, 'amber')) {
            return null;
        }
        if ($key === 'hdl' || (isset($lowerBounds[$key]) && is_numeric($value) && (float) $value < $lowerBounds[$key])) {
            return 'Low';
        }
        return 'High';
    };

    $flaggedCount = $sections->flatten(1)->filter(fn ($m) => in_array($metricStatusLabel($m['key'], $m['value'], $metricStatusClass($m['key'], $m['value'])), ['High', 'Low'], true))->count();
    $sourceLabel = $sourceLabels[$checkup->report_source] ?? ($checkup->report_source ?: null);
    $wrapper = $collapsible ? 'details' : 'article';
@endphp

<{{ $wrapper }} class="group rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden" @if($collapsible && $open) open @endif>
    @if($collapsible)<summary class="list-none cursor-pointer [&::-webkit-details-marker]:hidden">@endif
    <div class="flex items-start justify-between gap-3 border-b border-gray-100 bg-gray-50 px-5 py-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-teal-600">{{ __('Health Check-up') }}</p>
            <h4 class="mt-1 text-base font-extrabold text-gray-900">
                {{ \Carbon\Carbon::parse($checkup->checkup_date)->translatedFormat('d M Y') }}
                <span class="{{ $compact ? 'block mt-0.5' : 'ml-1' }} text-xs font-semibold text-gray-400">{{ \Carbon\Carbon::parse($checkup->checkup_date)->diffForHumans() }}</span>
            </h4>
            @if($sourceLabel)
                <p class="mt-1 text-xs font-semibold text-gray-500">{{ __('Source: :source', ['source' => $sourceLabel]) }}</p>
            @endif
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $flaggedCount ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                {{ $flaggedCount ? trans_choice(':count result to watch|:count results to watch', $flaggedCount, ['count' => $flaggedCount]) : __('All normal') }}
            </span>
            @if($collapsible)
                <svg class="h-5 w-5 text-gray-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            @endif
        </div>
    </div>
    @if($collapsible)</summary>@endif

    <div class="space-y-5 p-5">
        @forelse($sections as $sectionTitle => $metrics)
            <section>
                <h5 class="text-xs font-extrabold uppercase tracking-wide text-gray-500">{{ $sectionTitle }}</h5>
                <div class="mt-3 grid grid-cols-1 gap-3 {{ $compact ? '' : 'sm:grid-cols-2 lg:grid-cols-3' }}">
                    @foreach($metrics as $metric)
                        @php
                            $statusClass = $metricStatusClass($metric['key'], $metric['value']);
                            $statusLabel = $metricStatusLabel($metric['key'], $metric['value'], $statusClass);
                        @endphp
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold text-gray-500">{{ $metric['label'] }}</p>
                            <div class="mt-1 flex flex-wrap items-baseline justify-between gap-x-2">
                                <p class="text-sm font-extrabold {{ $statusClass }}">
                                    {{ $valueWithUnit($metric['value'], $metric['unit'], $metric['decimals']) }}
                                </p>
                                @if($statusLabel)
                                    <span class="text-[11px] font-bold uppercase tracking-wide {{ $statusLabel === 'Normal' ? 'text-emerald-600' : ($statusClass === 'text-red-600' ? 'text-red-600' : 'text-amber-700') }}">{{ __($statusLabel) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-sm text-gray-500">{{ __('No test results were recorded for this check-up.') }}</p>
        @endforelse

        @if(filled($checkup->notes))
            <section class="rounded-xl border border-teal-100 bg-teal-50 px-4 py-3">
                <h5 class="text-xs font-extrabold uppercase tracking-wide text-teal-700">{{ __('Pharmacist Notes') }}</h5>
                <p class="mt-2 text-sm font-medium leading-6 text-teal-950">{{ $checkup->notes }}</p>
            </section>
        @endif
    </div>
</{{ $wrapper }}>
