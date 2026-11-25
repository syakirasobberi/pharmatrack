<x-app-layout>
    @php
        $field = 'w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
        $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->toDateString() : '';
    @endphp

    <div class="py-8 sm:py-12 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('pharmacist.medication.index', $patient->id) }}" class="text-sm font-bold text-blue-600 hover:text-blue-800">&larr; Back to Medications</a>

            <div class="mt-4 bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 sm:px-8 py-6 border-b border-gray-100 bg-emerald-50/60">
                    <h2 class="text-2xl font-extrabold text-gray-800">Edit Medication</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Patient: <strong class="text-gray-800">{{ $patient->user->name }}</strong>
                        · Status: <strong>{{ $medication->status() }}</strong>
                    </p>
                    <p class="text-xs text-emerald-800 mt-3 font-semibold">
                        Use this to correct the record. To end a medication, use <em>Stop</em> on the Medications page instead, so the reason is kept.
                    </p>
                </div>

                <form method="POST" action="{{ route('pharmacist.medication.update', $medication->id) }}" class="p-6 sm:p-8 space-y-5">
                    @csrf
                    @method('PATCH')

                    @if($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-bold text-gray-700 mb-1">Medication Name <span class="text-red-500">*</span></label>
                            <input id="name" type="text" name="name" required maxlength="255" value="{{ old('name', $medication->name) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="dosage" class="block text-sm font-bold text-gray-700 mb-1">Dosage / How to Take <span class="text-red-500">*</span></label>
                            <input id="dosage" type="text" name="dosage" required maxlength="255" value="{{ old('dosage', $medication->dosage) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="frequency" class="block text-sm font-bold text-gray-700 mb-1">Frequency</label>
                            <input id="frequency" type="text" name="frequency" maxlength="255" value="{{ old('frequency', $medication->frequency) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-bold text-gray-700 mb-1">Notes</label>
                            <input id="notes" type="text" name="notes" maxlength="255" value="{{ old('notes', $medication->notes) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="start_date" class="block text-sm font-bold text-gray-700 mb-1">Start Date</label>
                            <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $date($medication->start_date)) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-bold text-gray-700 mb-1">End Date <span class="font-normal text-gray-400">(blank = ongoing)</span></label>
                            <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $date($medication->end_date)) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="last_taken" class="block text-sm font-bold text-gray-700 mb-1">Latest Dose Date</label>
                            <input id="last_taken" type="date" name="last_taken" max="{{ today()->toDateString() }}" value="{{ old('last_taken', $date($medication->last_taken)) }}" class="{{ $field }}">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('pharmacist.medication.index', $patient->id) }}" class="px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 font-bold hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
