<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    @php
        // Kira jumlah pesakit & checkup hari ini
        $assignedPatientIds = \App\Models\Patient::assignedTo(auth()->user())->pluck('id');
        $totalPatients = $assignedPatientIds->count();
        $todayCheckups = \App\Models\HealthCheckup::whereIn('patient_id', $assignedPatientIds)
            ->whereDate('created_at', today())
            ->count();
        
        // Tarik 5 pesakit yang baru didaftarkan
        $recentPatients = \App\Models\Patient::assignedTo(auth()->user())->with(['user', 'pharmacist'])->latest()->take(5)->get();
        
        // Tarik rekod pesakit yang berisiko (Gula Tinggi atau Kolesterol Tinggi) untuk panel Alert
        $healthAlerts = \App\Models\HealthCheckup::with('patient.user')
                            ->whereIn('patient_id', $assignedPatientIds)
                            ->where(function ($query) {
                                $query->where('blood_sugar', '>=', 5.6)
                                    ->orWhere('cholesterol', '>=', 5.2);
                            })
                            ->latest()
                            ->take(4)
                            ->get();

        $medicationAlerts = \App\Models\Medication::with('patient.user')
                            ->whereIn('patient_id', $assignedPatientIds)
                            ->where(function ($query) {
                                $query->whereNull('last_taken')
                                    ->orWhereDate('last_taken', '<', today()->subDays(7))
                                    ->orWhereBetween('end_date', [today(), today()->addDays(7)]);
                            })
                            ->latest()
                            ->take(5)
                            ->get();

        $patientsNeedingCheckup = \App\Models\Patient::assignedTo(auth()->user())
                            ->with(['user', 'healthCheckups' => fn ($query) => $query->latest('checkup_date')])
                            ->get()
                            ->filter(function ($patient) {
                                $latest = $patient->healthCheckups->first();

                                return ! $latest || \Carbon\Carbon::parse($latest->checkup_date)->lt(today()->subDays(90));
                            })
                            ->take(5);

        $workflowAlerts = $healthAlerts->count() + $medicationAlerts->count() + $patientsNeedingCheckup->count();
    @endphp

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-8 shadow-lg text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 right-20 w-32 h-32 bg-blue-400 opacity-20 rounded-full blur-xl"></div>
                
                <div class="relative z-10">
                    <h1 class="text-3xl font-extrabold mb-1">Welcome back, Pharmacist</h1>
                    <p class="text-blue-100 text-sm">Here is the latest health summary for your community patients today.</p>
                </div>
                <div class="relative z-10 flex gap-3">
                    <div class="bg-white/20 backdrop-blur-md px-4 py-2 rounded-xl border border-white/20 text-center">
                        <span class="block text-xs text-blue-100 font-bold uppercase tracking-wider">Date</span>
                        <span class="block text-sm font-extrabold">{{ now()->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            <!--<div class="mb-8 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
    <div>
        <h3 class="text-xl font-extrabold text-blue-900 flex items-center gap-2 mb-1">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-700 border border-blue-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </span>
            Quick Patient Scan
        </h3>
        <p class="text-sm text-blue-700 font-medium">
            Scan existing patient's face at the counter to automatically open their medical records.
        </p>
    </div>
    
    <a href="{{ route('pharmacist.quickScan') }}" class="flex-shrink-0 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg flex items-center gap-2 transition-transform transform hover:scale-105">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        Open Counter Camera
    </a>
</div>-->

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 flex items-center gap-4">
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center border border-blue-100 shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-bold">Total Patients</p>
                        <h3 class="text-2xl font-extrabold text-gray-800">{{ $totalPatients }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 flex items-center gap-4">
                    <div class="w-14 h-14 bg-green-50 text-green-600 rounded-xl flex items-center justify-center border border-green-100 shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-3-3v6m8-3a8 8 0 11-16 0 8 8 0 0116 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-bold">Check-ups Today</p>
                        <h3 class="text-2xl font-extrabold text-gray-800">{{ $todayCheckups }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 flex items-center gap-4">
                    <div class="w-14 h-14 bg-red-50 text-red-600 rounded-xl flex items-center justify-center border border-red-100 shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-bold">Workflow Alerts</p>
                        <h3 class="text-2xl font-extrabold text-gray-800">{{ $workflowAlerts }}</h3>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-extrabold text-lg text-gray-800 flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 border border-blue-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path>
                                </svg>
                            </span>
                            Recently Added Patients
                        </h3>
                        <a href="{{ route('pharmacist.patients.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-bold">View All &rarr;</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                                <tr>
                                    <th class="py-3 px-4 rounded-tl-lg">Patient Name</th>
                                    <th class="py-3 px-4">Gender / Age</th>
                                    <th class="py-3 px-4">BMI</th>
                                    <th class="py-3 px-4 rounded-tr-lg text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($recentPatients as $pt)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-4 font-bold text-gray-800 flex items-center gap-3">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($pt->user->name) }}&background=eff6ff&color=1d4ed8" class="w-8 h-8 rounded-full">
                                        <span>
                                            <span class="block">{{ $pt->user->name }}</span>
                                            <span class="block text-xs text-gray-400 font-semibold">Assigned Pharmacist: {{ $pt->pharmacist?->name ?? 'Unassigned' }}</span>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-gray-600">{{ $pt->gender }}, {{ $pt->age }}y</td>
                                    <td class="py-4 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $pt->bmi >= 25 ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }}">
                                            {{ number_format($pt->bmi, 1) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <a href="{{ route('pharmacist.patients.show', $pt->id) }}" class="inline-flex items-center justify-center px-4 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-bold rounded-lg transition-colors text-xs border border-blue-100 shadow-sm">
                                            View Profile
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500 italic">No patients registered yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-extrabold text-lg text-gray-800 mb-6 flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 border border-red-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                            </svg>
                        </span>
                        Alerts & Reminders
                    </h3>
                    
                    <div class="space-y-4">
                        @forelse($healthAlerts as $alert)
                        <div class="p-4 border border-red-100 bg-red-50/30 rounded-xl relative overflow-hidden group hover:bg-red-50 transition-colors">
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500"></div>
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="font-bold text-sm text-gray-800">{{ $alert->patient->user->name ?? 'Unknown' }}</h4>
                                <span class="text-[10px] font-bold text-gray-400">{{ \Carbon\Carbon::parse($alert->checkup_date)->format('d M') }}</span>
                            </div>
                            
                            <div class="text-xs text-gray-600 mt-2 space-y-1">
                                @if($alert->blood_sugar >= 5.6)
                                    <p><span class="text-red-600 font-bold">↑ High Sugar:</span> {{ $alert->blood_sugar }} mmol/L</p>
                                @endif
                                @if($alert->cholesterol >= 5.2)
                                    <p><span class="text-orange-600 font-bold">↑ High Cholesterol:</span> {{ $alert->cholesterol }} mmol/L</p>
                                @endif
                            </div>
                            
                            <a href="{{ route('pharmacist.patients.show', $alert->patient_id) }}" class="mt-3 text-xs font-bold text-blue-600 hover:text-blue-800 inline-block">Review Case &rarr;</a>
                        </div>
                        @empty
                        @if($workflowAlerts === 0)
                        <div class="p-6 border border-green-100 bg-green-50 rounded-xl text-center">
                            <p class="text-sm font-bold text-green-800">All Clear!</p>
                            <p class="text-xs text-green-600 mt-1">No critical health alerts detected recently.</p>
                        </div>
                        @endif
                        @endforelse

                        @foreach($medicationAlerts as $medication)
                            @php
                                $lastTaken = $medication->last_taken ? \Carbon\Carbon::parse($medication->last_taken) : null;
                                $endDate = $medication->end_date ? \Carbon\Carbon::parse($medication->end_date) : null;
                                $isEndingSoon = $endDate && $endDate->isFuture() && today()->diffInDays($endDate) <= 7;
                            @endphp
                            <div class="p-4 border border-amber-100 bg-amber-50/50 rounded-xl relative overflow-hidden">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500"></div>
                                <h4 class="font-bold text-sm text-gray-800">{{ $medication->patient->user->name ?? 'Unknown' }}</h4>
                                <p class="text-xs text-amber-700 mt-2">
                                    {{ $medication->name }}:
                                    @if($isEndingSoon)
                                        treatment ends on {{ $endDate->format('d M Y') }}.
                                    @elseif($lastTaken)
                                        last dose was {{ $lastTaken->diffInDays(today()) }} days ago.
                                    @else
                                        latest dose date is missing.
                                    @endif
                                </p>
                                <a href="{{ route('pharmacist.medication.index', $medication->patient_id) }}" class="mt-3 text-xs font-bold text-blue-600 hover:text-blue-800 inline-block">Review Medication &rarr;</a>
                            </div>
                        @endforeach

                        @foreach($patientsNeedingCheckup as $patientDue)
                            <div class="p-4 border border-blue-100 bg-blue-50/40 rounded-xl relative overflow-hidden">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                                <h4 class="font-bold text-sm text-gray-800">{{ $patientDue->user->name ?? 'Unknown' }}</h4>
                                <p class="text-xs text-blue-700 mt-2">
                                    {{ $patientDue->healthCheckups->first()
                                        ? 'Last check-up was on ' . \Carbon\Carbon::parse($patientDue->healthCheckups->first()->checkup_date)->format('d M Y') . '.'
                                        : 'No health check-up has been recorded yet.' }}
                                </p>
                                <a href="{{ route('pharmacist.checkups.create', $patientDue->id) }}" class="mt-3 text-xs font-bold text-blue-600 hover:text-blue-800 inline-block">Schedule Check-up &rarr;</a>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
