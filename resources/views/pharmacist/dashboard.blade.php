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

    <!-- Main Container with Ambient Background to prevent "kosong" look -->
    <div class="relative min-h-screen bg-slate-50 py-8 overflow-hidden font-sans">
        
        <!-- Decorative Ambient Background Blobs -->
        <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
            <div class="absolute top-[-10%] left-[-10%] w-[40vw] h-[40vw] rounded-full bg-blue-300/20 blur-[100px] mix-blend-multiply"></div>
            <div class="absolute bottom-[-10%] right-[-5%] w-[50vw] h-[50vw] rounded-full bg-indigo-300/20 blur-[120px] mix-blend-multiply"></div>
            <div class="absolute top-[20%] right-[15%] w-[30vw] h-[30vw] rounded-full bg-cyan-200/20 blur-[90px] mix-blend-multiply"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Hero Section -->
            <div class="bg-gradient-to-br from-blue-700 via-indigo-600 to-blue-800 rounded-[2rem] p-8 shadow-2xl text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden border border-white/10">
                
                <!-- Background Image -->
                <div class="absolute inset-0 mix-blend-overlay">
                    <img src="{{ asset('storage/login.png') }}" class="w-full h-full object-cover opacity-30">
                </div>
                
                <!-- Inner glow/gradient -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>

                <div class="relative z-10">
                    <h1 class="text-3xl md:text-4xl font-extrabold mb-2 tracking-tight text-white drop-shadow-md">Welcome back, Pharmacist</h1>
                    <p class="text-blue-100/90 text-sm md:text-base font-medium max-w-xl">
                        Here is the latest health summary and clinical decision alerts for your community patients today.
                    </p>
                </div>

                <div class="relative z-10 flex gap-3">
                    <div class="bg-white/10 backdrop-blur-xl px-5 py-3 rounded-2xl border border-white/20 text-center shadow-lg">
                        <span class="block text-[10px] text-blue-200 font-bold uppercase tracking-widest mb-1">Today's Date</span>
                        <span class="block text-lg font-extrabold text-white">{{ now()->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stat Card 1 -->
                <div class="bg-white/80 backdrop-blur-lg rounded-[1.5rem] p-6 shadow-sm hover:shadow-md border border-slate-200/60 transition-all duration-300 flex items-center gap-5 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 rounded-2xl flex items-center justify-center border border-blue-200/50 shadow-inner group-hover:scale-105 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold uppercase tracking-wider">Total Patients</p>
                        <h3 class="text-3xl font-extrabold text-slate-800">{{ $totalPatients }}</h3>
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div class="bg-white/80 backdrop-blur-lg rounded-[1.5rem] p-6 shadow-sm hover:shadow-md border border-slate-200/60 transition-all duration-300 flex items-center gap-5 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center border border-emerald-200/50 shadow-inner group-hover:scale-105 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-3-3v6m8-3a8 8 0 11-16 0 8 8 0 0116 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold uppercase tracking-wider">Check-ups Today</p>
                        <h3 class="text-3xl font-extrabold text-slate-800">{{ $todayCheckups }}</h3>
                    </div>
                </div>

                <!-- Stat Card 3 -->
                <div class="bg-white/80 backdrop-blur-lg rounded-[1.5rem] p-6 shadow-sm hover:shadow-md border border-slate-200/60 transition-all duration-300 flex items-center gap-5 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-rose-50 to-rose-100 text-rose-600 rounded-2xl flex items-center justify-center border border-rose-200/50 shadow-inner group-hover:scale-105 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold uppercase tracking-wider">Active Alerts</p>
                        <h3 class="text-3xl font-extrabold text-slate-800">{{ $workflowAlerts }}</h3>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Patient Table -->
                <div class="lg:col-span-2 bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-[2rem] p-7 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-extrabold text-xl text-slate-800 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path>
                                </svg>
                            </div>
                            Recently Added Patients
                        </h3>
                        <a href="{{ route('pharmacist.patients.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-bold px-4 py-2 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">View All Directory</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left border-separate border-spacing-y-2">
                            <thead class="text-slate-500 font-bold uppercase tracking-wider text-xs">
                                <tr>
                                    <th class="py-3 px-4">Patient Profile</th>
                                    <th class="py-3 px-4">Demographics</th>
                                    <th class="py-3 px-4">BMI Status</th>
                                    <th class="py-3 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPatients as $pt)
                                <tr class="bg-slate-50 hover:bg-blue-50/50 transition-colors group rounded-2xl shadow-sm">
                                    <td class="py-4 px-4 font-bold text-slate-800 flex items-center gap-4 rounded-l-2xl border-y border-l border-slate-100">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($pt->user->name) }}&background=e0e7ff&color=3730a3&bold=true" class="w-10 h-10 rounded-full shadow-sm group-hover:ring-2 ring-blue-200 transition-all">
                                        <span>
                                            <span class="block text-base">{{ $pt->user->name }}</span>
                                            <span class="block text-xs text-slate-400 font-medium mt-0.5">Pharmacist: {{ $pt->pharmacist?->name ?? 'Unassigned' }}</span>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-slate-600 border-y border-slate-100 font-medium">{{ $pt->gender }}, {{ $pt->age }}y</td>
                                    <td class="py-4 px-4 border-y border-slate-100">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm inline-flex items-center gap-1.5 {{ $pt->bmi >= 25 ? 'bg-orange-100 text-orange-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $pt->bmi >= 25 ? 'bg-orange-500' : 'bg-emerald-500' }}"></span>
                                            {{ number_format($pt->bmi, 1) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right rounded-r-2xl border-y border-r border-slate-100">
                                        <a href="{{ route('pharmacist.patients.show', $pt->id) }}" class="inline-flex items-center justify-center px-4 py-2 bg-white text-slate-700 hover:text-blue-700 font-bold rounded-xl transition-all text-xs border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-200">
                                            View Record
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-slate-400 font-medium bg-slate-50 rounded-2xl border border-dashed border-slate-200">No patients registered yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Column: CDSS Alerts -->
                <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-[2rem] p-7 shadow-sm flex flex-col">
                    <h3 class="font-extrabold text-xl text-slate-800 mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 relative">
                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            </span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        Clinical Alerts
                    </h3>
                    
                    <div class="space-y-4 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                        
                        <!-- Health Alerts -->
                        @forelse($healthAlerts as $alert)
                        <div class="p-4 border border-rose-100 bg-rose-50/50 rounded-2xl relative group hover:bg-rose-50 transition-colors shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold text-sm text-slate-800">{{ $alert->patient->user->name ?? 'Unknown' }}</h4>
                                <span class="px-2 py-1 bg-white rounded-md text-[10px] font-bold text-rose-500 shadow-sm border border-rose-100">{{ \Carbon\Carbon::parse($alert->checkup_date)->format('d M') }}</span>
                            </div>
                            
                            <div class="text-xs text-slate-600 space-y-1.5 bg-white/50 p-2.5 rounded-xl border border-rose-100/50">
                                @if($alert->blood_sugar >= 5.6)
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span class="text-rose-700 font-bold">High Sugar:</span> {{ $alert->blood_sugar }} mmol/L
                                    </div>
                                @endif
                                @if($alert->cholesterol >= 5.2)
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                        <span class="text-orange-700 font-bold">High Cholesterol:</span> {{ $alert->cholesterol }} mmol/L
                                    </div>
                                @endif
                            </div>
                            
                            <a href="{{ route('pharmacist.patients.show', $alert->patient_id) }}" class="mt-3 text-xs font-bold text-rose-600 hover:text-rose-800 flex items-center gap-1 group-hover:gap-2 transition-all">
                                Review Case <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                        @empty
                        @if($workflowAlerts === 0)
                        <div class="p-8 border border-emerald-100 bg-emerald-50/50 rounded-2xl text-center shadow-sm">
                            <div class="w-12 h-12 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="text-sm font-bold text-emerald-800">All Clear!</p>
                            <p class="text-xs text-emerald-600 mt-1 font-medium">No critical health alerts detected.</p>
                        </div>
                        @endif
                        @endforelse

                        <!-- Medication Alerts -->
                        @foreach($medicationAlerts as $medication)
                            @php
                                $lastTaken = $medication->last_taken ? \Carbon\Carbon::parse($medication->last_taken) : null;
                                $endDate = $medication->end_date ? \Carbon\Carbon::parse($medication->end_date) : null;
                                $isEndingSoon = $endDate && $endDate->isFuture() && today()->diffInDays($endDate) <= 7;
                            @endphp
                            <div class="p-4 border border-amber-200/60 bg-amber-50/50 rounded-2xl group shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002 2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    <h4 class="font-bold text-sm text-slate-800">{{ $medication->patient->user->name ?? 'Unknown' }}</h4>
                                </div>
                                
                                <p class="text-xs text-amber-800 font-medium bg-white/60 p-2.5 rounded-xl border border-amber-100/50">
                                    <span class="font-bold">{{ $medication->name }}:</span>
                                    @if($isEndingSoon)
                                        Treatment ends on {{ $endDate->format('d M Y') }}.
                                    @elseif($lastTaken)
                                        Last dose was {{ $lastTaken->diffInDays(today()) }} days ago.
                                    @else
                                        Latest dose date is missing.
                                    @endif
                                </p>
                                <a href="{{ route('pharmacist.medication.index', $medication->patient_id) }}" class="mt-3 text-xs font-bold text-amber-600 hover:text-amber-800 flex items-center gap-1 group-hover:gap-2 transition-all">
                                    Manage Medication <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        @endforeach

                        <!-- Check-up Reminders -->
                        @foreach($patientsNeedingCheckup as $patientDue)
                            <div class="p-4 border border-blue-200/60 bg-blue-50/50 rounded-2xl group shadow-sm">
                                <h4 class="font-bold text-sm text-slate-800 mb-2">{{ $patientDue->user->name ?? 'Unknown' }}</h4>
                                <p class="text-xs text-blue-800 font-medium bg-white/60 p-2.5 rounded-xl border border-blue-100/50">
                                    {{ $patientDue->healthCheckups->first()
                                        ? 'Last check-up: ' . \Carbon\Carbon::parse($patientDue->healthCheckups->first()->checkup_date)->format('d M Y') . '.'
                                        : 'No health check-up on record.' }}
                                </p>
                                <a href="{{ route('pharmacist.checkups.create', $patientDue->id) }}" class="mt-3 text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 group-hover:gap-2 transition-all">
                                    Schedule Check-up <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Optional: Add this style to your layout file for cleaner scrollbars in the alert panel -->
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
    </style>
</x-app-layout>