<x-app-layout>
    @php
        $groups = [
            'Vital Signs'   => ['blood_pressure', 'heart_rate', 'haemoglobin'],
            'Blood Sugar'   => ['blood_sugar', 'hba1c'],
            'Liver Panel'   => ['albumin_globulin_ratio', 'alkaline_phosphatase', 'aspartate_transaminase', 'alanine_transaminase', 'gamma_glutamyl_transferase'],
            'Kidney'        => ['sodium', 'renal_glucose'],
            'Lipid Panel'   => ['cholesterol', 'ldl', 'hdl'],
        ];
        // Show stored decimals without trailing zeros (4.80 -> 4.8) so edits are easy to read.
        $display = function ($value) {
            if ($value === null || $value === '') {
                return '';
            }
            return is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') : $value;
        };
    @endphp

    <div class="py-8 sm:py-12 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="text-sm font-bold text-blue-600 hover:text-blue-800">&larr; Back to Patient Profile</a>

            <div class="mt-4 bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 sm:px-8 py-6 border-b border-gray-100 bg-amber-50/60">
                    <h2 class="text-2xl font-extrabold text-gray-800">Edit Health Check-up</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Patient: <strong class="text-gray-800">{{ $patient->user->name }}</strong>
                        · Recorded {{ $checkup->created_at?->format('d M Y, g:i a') }}
                        @if($checkup->pharmacist) by {{ $checkup->pharmacist->name }} @endif
                        @if($checkup->edited_at)
                            · <span class="font-semibold text-amber-700">Last edited {{ $checkup->edited_at->format('d M Y, g:i a') }} by {{ $checkup->editor?->name ?? 'a removed user' }}</span>
                        @endif
                    </p>
                    <p class="text-xs text-amber-800 mt-3 font-semibold">
                        Use this to correct mistakes in the record. Every change is logged with your name and the old and new values.
                        @if($checkup->ai_suggestion)
                            Changing any reading clears the saved AI suggestion, because it was based on the old values.
                        @endif
                    </p>
                </div>

                <form method="POST" action="{{ route('pharmacist.checkups.update', $checkup->id) }}" class="p-6 sm:p-8 space-y-8">
                    @csrf
                    @method('PATCH')

                    @if($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="checkup_date" class="block text-sm font-bold text-gray-700 mb-1">Check-up Date <span class="text-red-500">*</span></label>
                            <input type="date" id="checkup_date" name="checkup_date" required max="{{ today()->toDateString() }}"
                                   value="{{ old('checkup_date', $checkup->checkup_date?->toDateString()) }}"
                                   class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="report_source" class="block text-sm font-bold text-gray-700 mb-1">Report Source</label>
                            <select id="report_source" name="report_source" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">— Select source —</option>
                                @foreach($reportSources as $value => $label)
                                    <option value="{{ $value }}" @selected(old('report_source', $checkup->report_source) === $value)>{{ $label }}</option>
                                @endforeach
                                {{-- Keep an unusual stored value selectable so saving doesn't silently drop it. --}}
                                @if($checkup->report_source && ! array_key_exists($checkup->report_source, $reportSources))
                                    <option value="{{ $checkup->report_source }}" @selected(old('report_source', $checkup->report_source) === $checkup->report_source)>{{ $checkup->report_source }}</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    @foreach($groups as $title => $fields)
                        <div>
                            <h3 class="text-sm font-extrabold uppercase tracking-wide text-gray-500 mb-3 border-b pb-2">{{ $title }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                @foreach($fields as $field)
                                    @php [$label, $unit, $placeholder] = $metrics[$field]; @endphp
                                    <div>
                                        <label for="{{ $field }}" class="block text-sm font-bold text-gray-700 mb-1">
                                            {{ $label }} @if($unit)<span class="font-normal text-gray-400">({{ $unit }})</span>@endif
                                        </label>
                                        <input type="text" inputmode="decimal" id="{{ $field }}" name="{{ $field }}"
                                               value="{{ old($field, $display($checkup->$field)) }}" placeholder="{{ $placeholder }}"
                                               class="w-full rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has($field) ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div>
                        <label for="notes" class="block text-sm font-bold text-gray-700 mb-1">Pharmacist Notes</label>
                        <textarea id="notes" name="notes" rows="3" maxlength="2000"
                                  class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $checkup->notes) }}</textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 pt-2 border-t border-gray-100">
                        <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 font-bold hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
