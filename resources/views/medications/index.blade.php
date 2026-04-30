<x-app-layout>
    @php
        $patientName = $patient->user->name ?: ('Patient #' . $patient->id);
        $totalMedications = $medications->count();
        $today = \Carbon\Carbon::today();

        $tbMedication = $medications->first(function ($medication) {
            $name = strtolower($medication->name);
            return str_contains($name, 'tb')
                || str_contains($name, 'tuberculosis')
                || str_contains($name, 'rifampicin')
                || str_contains($name, 'isoniazid');
        });

        $onTrackCount = 0;
        $reviewCount = 0;
        $restartCount = 0;

        foreach ($medications as $medication) {
            $daysSinceLastDose = \Carbon\Carbon::parse($medication->last_taken)->diffInDays($today);

            if ($daysSinceLastDose > 30) {
                $restartCount++;
            } elseif ($daysSinceLastDose > 7) {
                $reviewCount++;
            } else {
                $onTrackCount++;
            }
        }
    @endphp

    <div class="py-8 bg-slate-50 min-h-screen" x-data="{ addModalOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('pharmacist.patients.summary', $patient->id) }}" class="inline-flex items-center text-slate-500 hover:text-blue-700 font-bold transition-colors text-sm">
                    &larr; Back to Patient Summary
                </a>

                <button @click="addModalOpen = true" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md transition-colors">
                    Add Medication
                </button>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-6 md:px-8 bg-gradient-to-r from-emerald-600 to-teal-600 text-white">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">
                        <div class="flex items-center gap-4">
                            <img
                                src="https://ui-avatars.com/api/?name={{ urlencode($patientName) }}&background=ffffff&color=059669&size=128&font-size=0.35&bold=true"
                                alt="Patient avatar"
                                class="w-18 h-18 rounded-full border-4 border-white/50 shadow-sm"
                            >
                            <div>
                                <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-100">Medication Overview</p>
                                <h1 class="text-3xl font-extrabold">{{ $patientName }}</h1>
                                <div class="flex flex-wrap gap-3 text-sm text-emerald-50 mt-2">
                                    <span>{{ $patient->gender }}</span>
                                    <span>{{ $patient->age }} years</span>
                                    <span>{{ $patient->weight }} kg</span>
                                </div>
                            </div>
                        </div>

                        <div class="max-w-md text-sm text-emerald-50">
                            <p class="font-bold">How to use this page</p>
                            <p class="mt-1">Review the status cards first, then check the medication table. Use “Add Medication” to record a new prescription with start date, end date, and latest dose date.</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 md:p-8 space-y-6">
                    @if(session('success'))
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl font-bold">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl">
                            <p class="font-bold mb-2">Please fix these items before saving:</p>
                            <ul class="list-disc ml-5 text-sm space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Medications</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-800">{{ $totalMedications }}</p>
                            <p class="mt-1 text-sm text-slate-500">All active records for this patient.</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">On Track</p>
                            <p class="mt-3 text-3xl font-extrabold text-emerald-700">{{ $onTrackCount }}</p>
                            <p class="mt-1 text-sm text-emerald-600">Taken within the past 7 days.</p>
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Need Review</p>
                            <p class="mt-3 text-3xl font-extrabold text-amber-700">{{ $reviewCount }}</p>
                            <p class="mt-1 text-sm text-amber-600">Last dose was 8 to 30 days ago.</p>
                        </div>

                        <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-red-700">Need Restart</p>
                            <p class="mt-3 text-3xl font-extrabold text-red-700">{{ $restartCount }}</p>
                            <p class="mt-1 text-sm text-red-600">No recent dose for more than 30 days.</p>
                        </div>
                    </div>

                    @if($tbMedication)
                        @php
                            $lastTaken = \Carbon\Carbon::parse($tbMedication->last_taken);
                            $diffDays = $lastTaken->diffInDays($today);
                            $isNonCompliant = $diffDays > 3;
                            $startDate = \Carbon\Carbon::parse($tbMedication->start_date);
                            $endDate = \Carbon\Carbon::parse($tbMedication->end_date);
                            $totalDays = max(1, $startDate->diffInDays($endDate));
                            $daysPassed = max(0, $startDate->diffInDays($today));
                            $progressPercent = min(100, max(0, ($daysPassed / $totalDays) * 100));
                        @endphp

                        <div class="rounded-3xl border {{ $isNonCompliant ? 'border-red-300 bg-red-50' : 'border-emerald-200 bg-emerald-50' }} p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] {{ $isNonCompliant ? 'text-red-600' : 'text-emerald-700' }}">TB Treatment Tracker</p>
                                    <h2 class="mt-2 text-2xl font-extrabold {{ $isNonCompliant ? 'text-red-800' : 'text-emerald-800' }}">{{ $tbMedication->name }}</h2>
                                    <p class="mt-2 text-sm {{ $isNonCompliant ? 'text-red-700' : 'text-emerald-700' }}">
                                        {{ $isNonCompliant
                                            ? "Attention needed: the patient has not taken this TB medication for {$diffDays} days."
                                            : "Treatment appears active. Latest dose was " . ($diffDays === 0 ? 'today' : ($diffDays === 1 ? 'yesterday' : "{$diffDays} days ago")) . "." }}
                                    </p>
                                </div>

                                <div class="min-w-[260px]">
                                    <div class="flex justify-between text-xs font-bold {{ $isNonCompliant ? 'text-red-600' : 'text-emerald-700' }} mb-2">
                                        <span>Treatment progress</span>
                                        <span>{{ number_format($progressPercent, 1) }}%</span>
                                    </div>
                                    <div class="h-3 rounded-full overflow-hidden {{ $isNonCompliant ? 'bg-red-100' : 'bg-emerald-100' }}">
                                        <div class="h-3 rounded-full {{ $isNonCompliant ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                    <p class="mt-2 text-xs {{ $isNonCompliant ? 'text-red-600' : 'text-emerald-700' }}">
                                        Start: {{ $startDate->format('d M Y') }} | End: {{ $endDate->format('d M Y') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-5">
                        <h2 class="text-lg font-extrabold text-slate-800">Medication Status Guide</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4 text-sm">
                            <div class="rounded-2xl bg-white border border-emerald-200 p-4">
                                <p class="font-bold text-emerald-700">On Track</p>
                                <p class="text-slate-600 mt-1">The medication was taken recently and does not need urgent action.</p>
                            </div>
                            <div class="rounded-2xl bg-white border border-amber-200 p-4">
                                <p class="font-bold text-amber-700">Review Required</p>
                                <p class="text-slate-600 mt-1">Check with the patient because the last dose is getting delayed.</p>
                            </div>
                            <div class="rounded-2xl bg-white border border-red-200 p-4">
                                <p class="font-bold text-red-700">Needs Restart</p>
                                <p class="text-slate-600 mt-1">The therapy looks interrupted and may need pharmacist or doctor review.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-extrabold text-slate-800">Medication List</h2>
                                <p class="text-sm text-slate-500 mt-1">Each row shows what the patient is taking, for how long, and whether follow-up is needed.</p>
                            </div>
                            <button @click="addModalOpen = true" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition-colors">
                                Add Medication
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 text-slate-600 font-bold border-y border-slate-200">
                                    <tr>
                                        <th class="py-4 px-6">Medication</th>
                                        <th class="py-4 px-6">How To Take</th>
                                        <th class="py-4 px-6">Treatment Period</th>
                                        <th class="py-4 px-6">Latest Dose Date</th>
                                        <th class="py-4 px-6">Notes</th>
                                        <th class="py-4 px-6 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($medications as $medication)
                                        @php
                                            $lastTaken = \Carbon\Carbon::parse($medication->last_taken);
                                            $diffDays = $lastTaken->diffInDays($today);

                                            if ($diffDays > 30) {
                                                $statusClass = 'bg-red-100 text-red-700 border-red-200';
                                                $statusLabel = 'Needs Restart';
                                                $statusHint = 'More than 30 days since last dose';
                                            } elseif ($diffDays > 7) {
                                                $statusClass = 'bg-amber-100 text-amber-700 border-amber-200';
                                                $statusLabel = 'Review Required';
                                                $statusHint = '8 to 30 days since last dose';
                                            } else {
                                                $statusClass = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                                $statusLabel = 'On Track';
                                                $statusHint = 'Taken within the last 7 days';
                                            }
                                        @endphp

                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-4 px-6">
                                                <p class="font-bold text-slate-800">{{ $medication->name }}</p>
                                            </td>
                                            <td class="py-4 px-6 text-slate-600 font-medium">
                                                {{ $medication->dosage }}
                                            </td>
                                            <td class="py-4 px-6 text-slate-600">
                                                <div class="flex flex-col">
                                                    <span>{{ \Carbon\Carbon::parse($medication->start_date)->format('d M Y') }}</span>
                                                    <span class="text-xs text-slate-400">until {{ \Carbon\Carbon::parse($medication->end_date)->format('d M Y') }}</span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-slate-600">
                                                {{ $lastTaken->format('d M Y') }}
                                            </td>
                                            <td class="py-4 px-6 text-slate-500">
                                                {{ $medication->notes ?: 'No notes recorded' }}
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <div class="inline-flex flex-col items-end gap-1">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border {{ $statusClass }}">
                                                        {{ $statusLabel }}
                                                    </span>
                                                    <span class="text-[11px] text-slate-400">{{ $statusHint }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-14 text-center text-slate-500">
                                                <div class="flex flex-col items-center justify-center">
                                                    <p class="font-bold text-slate-700 text-lg">No medications recorded yet.</p>
                                                    <p class="text-sm mt-1">Use the Add Medication button to create the first prescription for this patient.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="addModalOpen"
            style="display: none;"
            class="fixed inset-0 z-50"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div
                x-show="addModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-white/12 backdrop-blur-[6px]"
                @click="addModalOpen = false"
                aria-hidden="true"
            ></div>

            <div class="absolute inset-0 overflow-y-auto">
                <div class="min-h-full flex items-center justify-center p-4 sm:p-6">
                    <div
                        x-show="addModalOpen"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                        class="w-full max-w-2xl bg-white rounded-[30px] text-left overflow-hidden shadow-[0_28px_90px_rgba(15,23,42,0.24)] transform transition-all border border-white/80"
                    >
                    <form method="POST" action="{{ route('pharmacist.medication.store', $patient->id) }}">
                        @csrf

                        <div class="relative overflow-hidden border-b border-slate-100 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.16),_transparent_38%),linear-gradient(135deg,#ffffff_0%,#f8fafc_100%)]">
                            <button
                                type="button"
                                @click="addModalOpen = false"
                                class="absolute top-4 right-1 h-10 w-10 rounded-full border border-slate-200 bg-white text-slate-400 hover:text-slate-700 hover:border-slate-300 transition-colors"
                                aria-label="Close form"
                            >
                                <span class="text-xl leading-none">&times;</span>
                            </button>

                            <div class="px-6 py-7 md:px-8">
                                <div class="flex items-start gap-4">
                                    <div class="h-14 w-14 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-sm">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a4 4 0 00-5.656 0M6.343 17.657a8 8 0 1111.314 0M9 10h.01M15 10h.01M12 14h.01"></path>
                                        </svg>
                                    </div>
                                    <div class="pr-12">
                                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-emerald-600">Medication Form</p>
                                        <h2 class="text-[28px] leading-tight font-extrabold text-slate-900 mt-2">Add medication for {{ $patientName }}</h2>
                                        <p class="text-sm text-slate-500 mt-3 max-w-xl">Record one medication clearly so the system can track timing, dose status, and treatment progress without confusion.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 md:p-8 bg-white">
                            <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-6">
                                <div class="space-y-5">
                                    <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500 mb-4">Medicine Details</p>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Medication name</label>
                                                <input type="text" name="name" value="{{ old('name') }}" placeholder="Metformin, Rifampicin, Isoniazid..." required class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition-colors">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-1.5">How should the patient take it?</label>
                                                <input type="text" name="dosage" value="{{ old('dosage') }}" placeholder="500mg twice daily after meals" required class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition-colors">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Extra notes</label>
                                                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Before breakfast, avoid dairy, take with water..." class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition-colors">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500 mb-4">Treatment Dates</p>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Start date</label>
                                                <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition-colors">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-1.5">End date</label>
                                                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition-colors">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Latest dose date</label>
                                                <input type="date" name="last_taken" value="{{ old('last_taken') }}" required class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition-colors">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Quick Guide</p>
                                        <ul class="mt-4 space-y-3 text-sm text-emerald-900">
                                            <li class="flex gap-2">
                                                <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                                <span>Use the full medication name to make searching easier later.</span>
                                            </li>
                                            <li class="flex gap-2">
                                                <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                                <span>Write dosage as the patient should actually take it.</span>
                                            </li>
                                            <li class="flex gap-2">
                                                <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                                <span>The latest dose date helps the status tracker know if treatment is on time.</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">TB Tip</p>
                                        <p class="mt-3 text-sm leading-6 text-slate-600">For long-term TB treatment, choose the correct start and end dates so the progress tracker can calculate treatment progress accurately.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white px-6 md:px-8 py-5 border-t border-slate-100">
                            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                                <button type="button" @click="addModalOpen = false" class="w-full sm:w-44 inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-5 py-3 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" class="w-full sm:w-56 inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-3 bg-emerald-600 text-sm font-bold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                                Save Medication
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                if (endDateInput.value) {
                    return;
                }

                const start = new Date(this.value);

                if (!isNaN(start)) {
                    start.setMonth(start.getMonth() + 6);

                    const year = start.getFullYear();
                    const month = String(start.getMonth() + 1).padStart(2, '0');
                    const day = String(start.getDate()).padStart(2, '0');

                    endDateInput.value = `${year}-${month}-${day}`;
                }
            });
        }
    </script>
</x-app-layout>
