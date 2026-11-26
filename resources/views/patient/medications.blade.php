<x-app-layout>
    <x-slot name="header">My Medications</x-slot>

    <div class="min-h-screen bg-gray-50 py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-gradient-to-br from-teal-600 to-cyan-700 p-6 sm:p-8 text-white shadow-lg">
                <p class="text-xs font-bold uppercase tracking-wide text-teal-100">My Medications</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold">Your medicines</h1>
                <p class="mt-2 max-w-2xl text-sm text-teal-100">
                    Tap <strong>"I took it today"</strong> after each dose, ask for a refill when you are running low, and leave your pharmacist a note.
                </p>
            </div>

            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($drugAlert = session('patient_drug_warnings'))
                @php $major = collect($drugAlert['warnings'])->contains('severity', 'major'); @endphp
                <div class="rounded-2xl border {{ $major ? 'border-red-300 bg-red-50 text-red-900' : 'border-amber-300 bg-amber-50 text-amber-900' }} px-5 py-4 text-sm" role="alert">
                    <p class="font-extrabold">{{ $drugAlert['name'] }} was saved, but please check with your pharmacist before taking it.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($drugAlert['warnings'] as $warning)
                            <li><strong>{{ $warning['title'] }}.</strong> {{ $warning['detail'] }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 font-semibold">{{ $major ? 'If you have already taken it and feel unwell (rash, swelling, difficulty breathing, unusual bleeding), get medical help straight away.' : 'Your pharmacist will also see this warning on your record.' }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                    <p class="font-bold">Please check your medication update.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($needsMedicationUpdate)
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-extrabold text-amber-900">Medication Update Needed</h2>
                            <p class="mt-1 text-sm text-amber-800">
                                Please check that your list below is still correct. It should be reviewed at least every 6 months; leave a note on anything that has changed.
                            </p>
                            @if($latestMedicationUpdate)
                                <p class="mt-1 text-xs font-semibold text-amber-700">
                                    Last updated: {{ \Carbon\Carbon::parse($latestMedicationUpdate)->format('d M Y') }}
                                </p>
                            @endif
                        </div>
                        <a href="#medication-list" class="inline-flex w-fit shrink-0 items-center rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-bold text-amber-800 hover:bg-amber-100">Review my list</a>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                    Your medication list is up to date.
                </div>
            @endif

            @if($adherence['status'] !== 'no_meds')
                @php $tone = \App\Support\Adherence::classes($adherence['status']); @endphp
                <div class="rounded-3xl border {{ $tone['border'] }} bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide {{ $tone['text'] }}">My Adherence (last {{ \App\Support\Adherence::WINDOW_DAYS }} days)</p>
                            @if($adherence['percent'] !== null)
                                <p class="mt-1 text-3xl font-extrabold text-gray-900">{{ $adherence['percent'] }}% <span class="text-base font-bold {{ $tone['text'] }}">{{ $adherence['label'] }}</span></p>
                                <p class="mt-1 text-sm text-gray-500">
                                    @if($adherence['status'] === 'good')
                                        Well done — keep tapping "I took it" each day.
                                    @else
                                        Some doses were not logged. If you are missing doses, talk to your pharmacist — they can help.
                                    @endif
                                </p>
                            @else
                                <p class="mt-1 text-lg font-extrabold text-gray-900">Start tracking today</p>
                                <p class="mt-1 text-sm text-gray-500">Tap <strong>"I took it today"</strong> under each medicine after you take it. Your pharmacist uses this to see how your treatment is going.</p>
                            @endif
                        </div>
                        @if($adherence['percent'] !== null)
                            <div class="h-3 w-full sm:w-56 rounded-full bg-gray-100 overflow-hidden" role="progressbar" aria-valuenow="{{ $adherence['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="h-3 rounded-full {{ $tone['bar'] }}" style="width: {{ $adherence['percent'] }}%"></div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @php
                $currentMeds = $patient ? $patient->medications->filter->isCurrent()->values() : collect();
                $pastMeds = $patient ? $patient->medications->reject->isCurrent()->values() : collect();
            @endphp

            <section id="medication-list" class="space-y-5 scroll-mt-6">
                <div class="flex items-end justify-between gap-3">
                    <h2 class="text-lg font-extrabold text-gray-900">Taking now <span class="text-sm font-bold text-gray-400">({{ $currentMeds->count() }})</span></h2>
                    <a href="#add-medication" onclick="document.getElementById('add-medication').open = true" class="text-sm font-bold text-teal-700 hover:text-teal-900">+ Add medication</a>
                </div>

                @forelse($currentMeds as $medication)
                        <article class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                            <div class="border-b border-gray-100 bg-gray-50 px-6 py-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-teal-600">Medication</p>
                                        <h2 class="mt-1 text-xl font-extrabold text-gray-900">
                                            {{ $medication->name }}
                                            @if($medication->status() !== 'Active')
                                                <span class="ml-2 align-middle px-2.5 py-1 rounded-full text-xs font-bold {{ $medication->status() === 'Stopped' ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-600' }}">{{ $medication->status() }}</span>
                                            @endif
                                        </h2>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $medication->dosage ?: 'Dosage not specified' }}
                                            @if($medication->frequency)
                                                <span class="text-gray-400"> | </span>{{ $medication->frequency }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 text-left shadow-sm sm:text-right">
                                        <p class="text-xs font-bold uppercase text-gray-500">Latest Dose</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900">
                                            {{ $medication->last_taken ? $medication->last_taken->format('d M Y') : 'Not updated yet' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if($medication->isCurrent() && ($medAdherence = $adherence['medications'][$medication->id] ?? null))
                                @php $medTone = \App\Support\Adherence::classes($medAdherence['status']); @endphp
                                <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-4">
                                        @include('medications.partials.dose-dots', ['last7' => $medAdherence['last7']])
                                        <span class="px-2.5 py-1 rounded-full border text-xs font-bold {{ $medTone['badge'] }}">
                                            {{ $medAdherence['percent'] !== null ? $medAdherence['percent'] . '% · ' : '' }}{{ $medAdherence['label'] }}
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($medAdherence['taken_today'])
                                            <span class="inline-flex items-center gap-1 rounded-xl bg-emerald-100 px-4 py-2.5 text-sm font-extrabold text-emerald-800">&#10003; Taken today</span>
                                            <form method="POST" action="{{ route('patient.medications.doses.destroy', $medication) }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="taken_on" value="{{ today()->toDateString() }}">
                                                <button type="submit" class="rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50">Undo</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('patient.medications.doses.store', $medication) }}">
                                                @csrf
                                                <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-emerald-700">I took it today</button>
                                            </form>
                                        @endif

                                        {{-- Forgot to log? Pick one of the previous 6 days. --}}
                                        @php $earlierDays = collect(array_slice($medAdherence['last7'], 0, 6))->reject(fn ($d) => $d['taken'] || ($medication->firstDoseDay() && $d['date']->lt($medication->firstDoseDay()))); @endphp
                                        @if($earlierDays->isNotEmpty())
                                        <details class="relative">
                                            <summary class="list-none cursor-pointer rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50">Earlier day…</summary>
                                            <form method="POST" action="{{ route('patient.medications.doses.store', $medication) }}" class="absolute right-0 z-10 mt-2 w-56 space-y-2 rounded-xl border border-gray-200 bg-white p-3 shadow-lg">
                                                @csrf
                                                <label for="taken_on_{{ $medication->id }}" class="block text-xs font-bold text-gray-700">I also took it on</label>
                                                <select id="taken_on_{{ $medication->id }}" name="taken_on" class="w-full rounded-lg border-gray-300 text-sm">
                                                    @foreach($earlierDays as $day)
                                                        @unless($day['taken'])
                                                            <option value="{{ $day['date']->toDateString() }}">{{ $day['date']->format('D, d M') }}</option>
                                                        @endunless
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="w-full rounded-lg bg-teal-600 px-3 py-2 text-xs font-bold text-white hover:bg-teal-700">Log dose</button>
                                            </form>
                                        </details>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if($medication->isCurrent())
                                @php $refill = $medication->refillStatus(); @endphp
                                <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-3 sm:flex-row sm:items-center sm:justify-between {{ $refill === 'overdue' ? 'bg-red-50' : ($refill === 'due' ? 'bg-amber-50' : '') }}">
                                    <div class="text-sm">
                                        @if($medication->supply_until || $medication->refill_requested_at)
                                            @include('medications.partials.supply-badge', ['medication' => $medication])
                                        @else
                                            <span class="text-xs text-gray-500">Running low? You can ask your pharmacist for a refill.</span>
                                        @endif
                                    </div>
                                    @unless($medication->refill_requested_at)
                                        <form method="POST" action="{{ route('patient.medications.refillRequest', $medication) }}">
                                            @csrf
                                            <button type="submit" class="rounded-xl px-4 py-2 text-xs font-extrabold {{ in_array($refill, ['due', 'overdue']) ? 'bg-teal-600 text-white hover:bg-teal-700' : 'border border-teal-200 bg-white text-teal-700 hover:bg-teal-50' }}">Request refill</button>
                                        </form>
                                    @endunless
                                </div>
                            @endif

                            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_320px]">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                        <p class="text-xs font-bold uppercase text-gray-500">Start Date</p>
                                        <p class="mt-1 font-bold text-gray-900">
                                            {{ $medication->start_date ? $medication->start_date->format('d M Y') : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                        <p class="text-xs font-bold uppercase text-gray-500">End Date</p>
                                        <p class="mt-1 font-bold text-gray-900">
                                            {{ $medication->end_date ? $medication->end_date->format('d M Y') : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 sm:col-span-2">
                                        <p class="text-xs font-bold uppercase text-gray-500">Current Note</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-800">
                                            {{ $medication->notes ?: 'No note recorded.' }}
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('patient.medications.update', $medication) }}" class="rounded-2xl border border-teal-100 bg-teal-50 p-4">
                                    @csrf
                                    @method('PATCH')

                                    <h3 class="font-extrabold text-teal-950">Quick Update</h3>
                                    <p class="mt-1 text-xs font-semibold text-teal-700">Keep this short. Your pharmacist can review it later.</p>

                                    <label class="mt-4 block text-sm font-bold text-teal-950" for="notes_{{ $medication->id }}">Note or question</label>
                                    <textarea
                                        id="notes_{{ $medication->id }}"
                                        name="notes"
                                        rows="2"
                                        maxlength="255"
                                        placeholder="Example: Still taking daily, no issue."
                                        class="mt-1 w-full rounded-xl border-teal-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    >{{ old('notes', $medication->notes) }}</textarea>

                                    <button type="submit" class="mt-4 w-full rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-teal-700">
                                        Save Update
                                    </button>
                                </form>
                            </div>
                        </article>
                @empty
                    <div class="rounded-3xl border-2 border-dashed border-gray-300 bg-white p-10 text-center shadow-sm">
                        <h3 class="text-lg font-extrabold text-gray-800">No current medications</h3>
                        <p class="mt-2 text-sm text-gray-500">Your pharmacist has no medicine recorded for you right now. If you are taking something, add it below.</p>
                    </div>
                @endforelse
            </section>

            @if($pastMeds->isNotEmpty())
                <details class="group rounded-3xl border border-gray-200 bg-white shadow-sm">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-4 [&::-webkit-details-marker]:hidden">
                        <span>
                            <span class="block text-base font-extrabold text-gray-900">Past medications ({{ $pastMeds->count() }})</span>
                            <span class="block text-xs font-semibold text-gray-500">Finished or stopped. Kept for your record.</span>
                        </span>
                        <svg class="h-5 w-5 text-gray-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </summary>
                    <div class="space-y-3 border-t border-gray-100 p-4 sm:p-6">
                        @foreach($pastMeds as $medication)
                            <div class="flex flex-col gap-2 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-bold text-gray-900">
                                        {{ $medication->name }}
                                        <span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $medication->status() === 'Stopped' ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-600' }}">{{ $medication->status() }}</span>
                                    </p>
                                    <p class="text-sm text-gray-600">{{ $medication->dosage ?: 'Dosage not specified' }}@if($medication->frequency) · {{ $medication->frequency }}@endif</p>
                                    @if($medication->notes)
                                        <p class="mt-1 text-xs text-gray-500">Note: {{ $medication->notes }}</p>
                                    @endif
                                </div>
                                <p class="text-xs font-semibold text-gray-500 sm:text-right">
                                    {{ $medication->start_date ? $medication->start_date->format('d M Y') : '—' }} → {{ $medication->end_date ? $medication->end_date->format('d M Y') : '—' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

            <details id="add-medication" class="group rounded-3xl border border-teal-100 bg-white p-6 shadow-sm scroll-mt-6" @if(($errors->any() && old('name') !== null) || ! $patient?->medications->count()) open @endif>
                <summary class="flex cursor-pointer list-none items-start justify-between gap-3 [&::-webkit-details-marker]:hidden">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-teal-600">Add Medication</p>
                        <h2 class="mt-1 text-xl font-extrabold text-gray-900">Taking something not listed?</h2>
                        <p class="mt-1 text-sm text-gray-500">Add it here, including vitamins or medicine from another clinic, so your pharmacist can check it is safe with your other medicines.</p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-sm font-bold text-white group-open:bg-gray-100 group-open:text-gray-700">
                        <svg class="h-4 w-4 transition-transform group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span class="group-open:hidden">Add</span><span class="hidden group-open:inline">Close</span>
                    </span>
                </summary>

                <form method="POST" action="{{ route('patient.medications.store') }}" class="mt-5 grid gap-4 lg:grid-cols-4">
                    @csrf

                    <div class="lg:col-span-2">
                        <label for="new_medication_name" class="block text-sm font-bold text-gray-700">Medication name</label>
                        <input
                            id="new_medication_name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Example: Metformin"
                            required
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                    </div>

                    <div>
                        <label for="new_medication_dosage" class="block text-sm font-bold text-gray-700">Dosage</label>
                        <input
                            id="new_medication_dosage"
                            type="text"
                            name="dosage"
                            value="{{ old('dosage') }}"
                            placeholder="Example: 500mg"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                    </div>

                    <div>
                        <label for="new_medication_last_taken" class="block text-sm font-bold text-gray-700">Latest taken</label>
                        <input
                            id="new_medication_last_taken"
                            type="date"
                            name="last_taken"
                            value="{{ old('last_taken', now()->format('Y-m-d')) }}"
                            max="{{ now()->format('Y-m-d') }}"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                    </div>

                    <div class="lg:col-span-2">
                        <label for="new_medication_frequency" class="block text-sm font-bold text-gray-700">How to take</label>
                        <input
                            id="new_medication_frequency"
                            type="text"
                            name="frequency"
                            value="{{ old('frequency') }}"
                            placeholder="Example: Once daily after meal"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                    </div>

                    <div class="lg:col-span-2">
                        <label for="new_medication_notes" class="block text-sm font-bold text-gray-700">Note</label>
                        <input
                            id="new_medication_notes"
                            type="text"
                            name="notes"
                            value="{{ old('notes') }}"
                            maxlength="255"
                            placeholder="Optional question or note"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                    </div>

                    <div class="lg:col-span-4">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-teal-700 sm:w-auto">
                            Add Medication
                        </button>
                    </div>
                </form>
            </details>

        </div>
    </div>
</x-app-layout>
