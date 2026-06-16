<x-app-layout>
    <x-slot name="header">My Medications</x-slot>

    <div class="min-h-screen bg-gray-50 py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-violet-700 p-8 text-white shadow-lg">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-100">Your Active Prescriptions</p>
                <h1 class="mt-2 text-3xl font-extrabold">Medication Schedule</h1>
                <p class="mt-2 max-w-2xl text-sm text-indigo-100">
                    Review your medication list and update the latest date you took each medication.
                </p>
            </div>

            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
                    {{ session('success') }}
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
                                Please review your medication list. Medication records should be updated at least once every 6 months.
                            </p>
                            @if($latestMedicationUpdate)
                                <p class="mt-1 text-xs font-semibold text-amber-700">
                                    Last updated: {{ \Carbon\Carbon::parse($latestMedicationUpdate)->format('d M Y') }}
                                </p>
                            @endif
                        </div>
                        <span class="inline-flex w-fit items-center rounded-xl bg-amber-100 px-4 py-2 text-sm font-bold text-amber-800">
                            Review today
                        </span>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                    Your medication list is up to date.
                </div>
            @endif

            <div class="rounded-3xl border border-indigo-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Add Medication</p>
                        <h2 class="mt-1 text-xl font-extrabold text-gray-900">Record Your Medication</h2>
                        <p class="mt-1 text-sm text-gray-500">Add medication you are currently taking so your record stays complete.</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">
                        Patient entry
                    </span>
                </div>

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
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="lg:col-span-4">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 sm:w-auto">
                            Add Medication
                        </button>
                    </div>
                </form>
            </div>

            @if($patient && $patient->medications->count() > 0)
                <div class="space-y-5">
                    @foreach($patient->medications as $medication)
                        <article class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                            <div class="border-b border-gray-100 bg-gray-50 px-6 py-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Medication</p>
                                        <h2 class="mt-1 text-xl font-extrabold text-gray-900">{{ $medication->name }}</h2>
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

                                <form method="POST" action="{{ route('patient.medications.update', $medication) }}" class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                                    @csrf
                                    @method('PATCH')

                                    <h3 class="font-extrabold text-indigo-950">Quick Update</h3>
                                    <p class="mt-1 text-xs font-semibold text-indigo-700">Keep this short. Your pharmacist can review it later.</p>

                                    <label class="mt-4 block text-sm font-bold text-indigo-950" for="last_taken_{{ $medication->id }}">Latest taken date</label>
                                    <input
                                        id="last_taken_{{ $medication->id }}"
                                        type="date"
                                        name="last_taken"
                                        value="{{ old('last_taken', $medication->last_taken?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                                        max="{{ now()->format('Y-m-d') }}"
                                        class="mt-1 w-full rounded-xl border-indigo-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >

                                    <label class="mt-4 block text-sm font-bold text-indigo-950" for="notes_{{ $medication->id }}">Note or question</label>
                                    <textarea
                                        id="notes_{{ $medication->id }}"
                                        name="notes"
                                        rows="2"
                                        maxlength="255"
                                        placeholder="Example: Still taking daily, no issue."
                                        class="mt-1 w-full rounded-xl border-indigo-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >{{ old('notes', $medication->notes) }}</textarea>

                                    <button type="submit" class="mt-4 w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
                                        Save Update
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-3xl border-2 border-dashed border-gray-300 bg-white p-12 text-center shadow-sm">
                    <h2 class="text-xl font-extrabold text-gray-800">No Medications on Record</h2>
                    <p class="mt-2 text-sm text-gray-500">Use the form above to add medication you are currently taking.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
