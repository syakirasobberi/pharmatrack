{{--
    Drug safety warnings list.
    $warnings  array from App\Support\DrugSafety
    $ackFor    (optional) medicine name the warnings were raised for: shows the
               "I have reviewed" checkbox + reason, to be placed inside a form.
--}}
@php
    $hasMajor = collect($warnings)->contains('severity', 'major');
    $typeLabels = ['allergy' => 'Allergy', 'interaction' => 'Interaction', 'duplicate' => 'Duplicate', 'condition' => 'Condition'];
@endphp

<div class="rounded-2xl border {{ $hasMajor ? 'border-red-300 bg-red-50' : 'border-amber-300 bg-amber-50' }} p-4" role="alert">
    <p class="flex items-center gap-2 text-sm font-extrabold {{ $hasMajor ? 'text-red-800' : 'text-amber-800' }}">
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        {{ isset($ackFor) ? 'Safety check for ' . $ackFor . ': ' : 'Safety check: ' }}{{ count($warnings) }} {{ \Illuminate\Support\Str::plural('warning', count($warnings)) }}
    </p>

    <ul class="mt-3 space-y-2">
        @foreach($warnings as $warning)
            @php $major = $warning['severity'] === 'major'; @endphp
            <li class="rounded-xl bg-white border {{ $major ? 'border-red-200' : 'border-amber-200' }} p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold uppercase {{ $major ? 'bg-red-600 text-white' : 'bg-amber-400 text-amber-950' }}">
                        {{ $major ? 'Major' : 'Moderate' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">{{ $typeLabels[$warning['type']] ?? $warning['type'] }}</span>
                    <span class="text-sm font-bold text-slate-800">{{ $warning['title'] }}</span>
                </div>
                <p class="mt-1.5 text-sm text-slate-600">{{ $warning['detail'] }}</p>
            </li>
        @endforeach
    </ul>

    @isset($ackFor)
        <div class="mt-4 rounded-xl bg-white border border-slate-200 p-3 space-y-2">
            <input type="hidden" name="acknowledged_name" value="{{ $ackFor }}">
            <label class="flex items-start gap-2 text-sm font-bold text-slate-800">
                <input type="checkbox" name="acknowledge_warnings" value="1" required class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                <span>I have reviewed these warnings and still want to save {{ $ackFor }}.</span>
            </label>
            <input type="text" name="override_reason" maxlength="255" value="{{ old('override_reason') }}"
                   placeholder="Reason (optional), e.g. doctor confirmed, allergy was mild rash only"
                   class="w-full rounded-lg border-slate-300 text-sm">
            <p class="text-xs text-slate-500">Or change the medication above and save again to re-check. Overrides are recorded in the system log.</p>
        </div>
    @endisset

    <p class="mt-3 text-[11px] text-slate-500">Decision support only, covering common medicines. Always use professional judgement.</p>
</div>
