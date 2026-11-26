<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    @php
        $pharmacist = auth()->user();
        $assignedPatientIds = \App\Models\Patient::assignedTo($pharmacist)->pluck('id');
        $totalPatients = $assignedPatientIds->count();

        $todayCheckups = \App\Models\HealthCheckup::whereIn('patient_id', $assignedPatientIds)
            ->whereDate('created_at', today())
            ->count();

        // Quick Scan can only find patients with a registered face.
        $facesRegistered = \App\Models\Patient::assignedTo($pharmacist)
            ->whereNotNull('face_descriptor')->where('face_descriptor', '!=', '')
            ->count();
        $facesPending = $totalPatients - $facesRegistered;

        $recentPatients = \App\Models\Patient::assignedTo($pharmacist)
            ->with(['user', 'latestCheckup'])
            ->latest()->take(5)->get();

        // Health alerts use each patient's LATEST check-up only, so an old reading
        // that has since improved does not keep raising an alert.
        $bpParts = function ($bp) {
            return preg_match('/(\d{2,3})\s*\/\s*(\d{2,3})/', (string) $bp, $m) ? [(int) $m[1], (int) $m[2]] : null;
        };
        $healthAlertsAll = \App\Models\Patient::assignedTo($pharmacist)
            ->with(['user', 'latestCheckup'])
            ->whereHas('latestCheckup')
            ->get()
            ->map(function ($patient) use ($bpParts) {
                $c = $patient->latestCheckup;
                $flags = [];
                if (is_numeric($c->blood_sugar) && ($c->blood_sugar < 3.9 || $c->blood_sugar > 6.0)) {
                    $flags[] = ['Blood sugar', $c->blood_sugar . ' mmol/L', $c->blood_sugar >= 7.0 || $c->blood_sugar < 3.0 ? 'rose' : 'amber'];
                }
                if (is_numeric($c->cholesterol) && $c->cholesterol >= 5.2) {
                    $flags[] = ['Cholesterol', $c->cholesterol . ' mmol/L', $c->cholesterol >= 6.2 ? 'rose' : 'amber'];
                }
                if (($bp = $bpParts($c->blood_pressure)) && ($bp[0] >= 140 || $bp[1] >= 90)) {
                    $flags[] = ['Blood pressure', $c->blood_pressure . ' mmHg', $bp[0] >= 160 || $bp[1] >= 100 ? 'rose' : 'amber'];
                }
                return $flags ? (object) ['patient' => $patient, 'checkup' => $c, 'flags' => $flags] : null;
            })
            ->filter()
            ->sortBy([fn ($a, $b) => [$b->checkup->checkup_date, $b->checkup->id] <=> [$a->checkup->checkup_date, $a->checkup->id]])
            ->values();
        $healthAlerts = $healthAlertsAll->take(4);

        $medicationAlertQuery = \App\Models\Medication::whereIn('patient_id', $assignedPatientIds)
            ->current() // stopped or finished medications need no follow-up
            ->where(function ($query) {
                $query->whereNull('last_taken')
                    ->orWhereDate('last_taken', '<', today()->subDays(7))
                    ->orWhereBetween('end_date', [today(), today()->addDays(7)]);
            });
        $medicationAlertCount = (clone $medicationAlertQuery)->count();
        $medicationAlerts = $medicationAlertQuery->with('patient.user')->latest()->take(5)->get();

        // Adherence below 80% (only patients who log doses; never-tracked patients are not flagged).
        $trackingMap = \App\Support\Adherence::trackingSinceMany($assignedPatientIds);
        $adherenceAlertsAll = \App\Models\Patient::assignedTo($pharmacist)
            ->whereIn('id', array_keys($trackingMap))
            ->with(['user', 'currentMedications.doses' => fn ($q) => $q->whereDate('taken_on', '>=', \App\Support\Adherence::windowStart())])
            ->get()
            ->map(fn ($p) => (object) ['patient' => $p, 'adherence' => \App\Support\Adherence::forPatient($p, $p->currentMedications, $trackingMap[$p->id] ?? null)])
            ->filter(fn ($a) => $a->adherence['percent'] !== null && $a->adherence['percent'] < \App\Support\Adherence::GOOD)
            ->sortBy(fn ($a) => $a->adherence['percent'])
            ->values();
        $adherenceAlerts = $adherenceAlertsAll->take(3);

        // Refills: requested by the patient, or supply running out within the notice window / already out.
        $refillAlertsAll = \App\Models\Medication::whereIn('patient_id', $assignedPatientIds)
            ->current()
            ->where(fn ($q) => $q->whereNotNull('refill_requested_at')
                ->orWhereDate('supply_until', '<=', today()->addDays(\App\Models\Medication::REFILL_NOTICE_DAYS)))
            ->with('patient.user')
            ->get()
            ->filter(fn ($m) => $m->refill_requested_at || in_array($m->refillStatus(), ['due', 'overdue'], true))
            ->sortBy(fn ($m) => [$m->refill_requested_at ? 0 : 1, $m->supply_until?->timestamp ?? 0])
            ->values();
        $refillAlerts = $refillAlertsAll->take(3);

        // Medicines patients recorded themselves that no pharmacist has reviewed, with safety warnings.
        $reviewAlertsAll = \App\Models\Medication::whereIn('patient_id', $assignedPatientIds)
            ->current()
            ->whereNull('reviewed_at')
            ->with(['patient.user', 'patient.medicalHistory', 'patient.currentMedications'])
            ->latest()
            ->get()
            ->map(fn ($med) => (object) [
                'med' => $med,
                'warnings' => \App\Support\DrugSafety::checkNew(
                    $med->name,
                    $med->patient->currentMedications->where('id', '!=', $med->id),
                    $med->patient->medicalHistory,
                ),
            ])
            ->sortBy(fn ($a) => \App\Support\DrugSafety::hasMajor($a->warnings) ? 0 : ($a->warnings ? 1 : 2))
            ->values();
        $reviewAlerts = $reviewAlertsAll->take(4);

        // Due = no check-up in the last 90 days.
        $dueQuery = \App\Models\Patient::assignedTo($pharmacist)
            ->whereDoesntHave('healthCheckups', fn ($query) => $query->whereDate('checkup_date', '>=', today()->subDays(90)));
        $dueCount = (clone $dueQuery)->count();
        $patientsNeedingCheckup = $dueQuery->with(['user', 'latestCheckup'])->take(5)->get();

        // When each due patient was last sent a check-up reminder (one query for the whole list).
        $lastReminderAt = \Illuminate\Notifications\DatabaseNotification::query()
            ->where('type', \App\Notifications\RoutineCheckupReminderNotification::class)
            ->where('notifiable_type', (new \App\Models\User)->getMorphClass())
            ->whereIn('notifiable_id', $patientsNeedingCheckup->pluck('user_id')->filter())
            ->selectRaw('notifiable_id, MAX(created_at) as last_at')
            ->groupBy('notifiable_id')
            ->pluck('last_at', 'notifiable_id');

        $workflowAlerts = $healthAlertsAll->count() + $medicationAlertCount + $adherenceAlertsAll->count() + $refillAlertsAll->count() + $reviewAlertsAll->count() + $dueCount;
        $shownAlerts = $healthAlerts->count() + $medicationAlerts->count() + $adherenceAlerts->count() + $refillAlerts->count() + $reviewAlerts->count() + $patientsNeedingCheckup->count();

        $bmiBadge = function ($bmi) {
            if (! is_numeric($bmi) || $bmi <= 0) return ['—', 'bg-slate-100 text-slate-500', 'bg-slate-400', 'Not recorded'];
            $label = number_format($bmi, 1);
            return match (true) {
                $bmi < 18.5 => [$label, 'bg-sky-100 text-sky-700', 'bg-sky-500', 'Underweight'],
                $bmi < 25 => [$label, 'bg-emerald-100 text-emerald-700', 'bg-emerald-500', 'Normal'],
                $bmi < 30 => [$label, 'bg-orange-100 text-orange-700', 'bg-orange-500', 'Overweight'],
                default => [$label, 'bg-rose-100 text-rose-700', 'bg-rose-500', 'Obese'],
            };
        };
    @endphp

    <div class="min-h-screen bg-slate-50 py-8 font-sans">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</div>
            @endif

            @if(blank($pharmacist->phone_number))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-900 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-extrabold">Add your WhatsApp phone number</p>
                            <p class="mt-1 text-sm font-medium text-amber-800">Patients can contact you from Contact Us after you update your phone number.</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-bold text-white hover:bg-amber-700">
                            Update Profile
                        </a>
                    </div>
                </div>
            @endif

            <!-- Hero + quick actions -->
            <div class="bg-gradient-to-br from-teal-700 via-teal-600 to-teal-800 rounded-[2rem] p-8 shadow-2xl text-white relative overflow-hidden border border-white/10">
                <div class="absolute inset-0 mix-blend-overlay">
                    <img src="{{ asset('storage/login.png') }}" alt="" class="w-full h-full object-cover opacity-30">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>

                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold mb-2 tracking-tight text-white drop-shadow-md">Welcome back, {{ $pharmacist->name }}</h1>
                        <p class="text-teal-100/90 text-sm md:text-base font-medium max-w-xl">
                            Here is the latest health summary and clinical alerts for your community patients today.
                        </p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-xl px-5 py-3 rounded-2xl border border-white/20 text-center shadow-lg shrink-0">
                        <span class="block text-[10px] text-teal-200 font-bold uppercase tracking-widest mb-1">Today's Date</span>
                        <span class="block text-lg font-extrabold text-white">{{ now()->format('d M Y') }}</span>
                    </div>
                </div>

                <div class="relative z-10 mt-7 flex flex-col lg:flex-row gap-3">
                    <a href="{{ route('pharmacist.quickScan') }}" id="dashboard-quick-scan"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-extrabold text-teal-700 shadow-lg hover:bg-teal-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V6a2 2 0 012-2h2M4 16v2a2 2 0 002 2h2m8-16h2a2 2 0 012 2v2m-4 12h2a2 2 0 002-2v-2M9 10h.01M15 10h.01M9.5 15a3.5 3.5 0 005 0"/></svg>
                        Quick Scan Patient
                    </a>
                    <a href="{{ route('pharmacist.patients.create') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 px-5 py-3 text-sm font-bold text-white border border-white/30 hover:bg-white/25 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Register Patient
                    </a>
                    <form method="GET" action="{{ route('pharmacist.patients.index') }}" class="flex flex-1 lg:max-w-md gap-2" role="search">
                        <label for="dashboard-search" class="sr-only">Find a patient</label>
                        <input id="dashboard-search" type="search" name="search" placeholder="Find patient by name, email or ID"
                               class="w-full rounded-xl border-0 bg-white/95 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 shadow-lg focus:ring-2 focus:ring-white">
                        <button type="submit" class="rounded-xl bg-teal-900/60 px-4 py-3 text-sm font-bold text-white border border-white/20 hover:bg-teal-900/80">Search</button>
                    </form>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <a href="{{ route('pharmacist.patients.index') }}" class="bg-white rounded-[1.5rem] p-5 shadow-sm hover:shadow-md border border-slate-200 transition-all flex items-center gap-4 group">
                    <div class="w-14 h-14 bg-gradient-to-br from-teal-50 to-teal-100 text-teal-600 rounded-2xl flex items-center justify-center border border-teal-200/50 shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Total Patients</p>
                        <h3 class="text-3xl font-extrabold text-slate-800">{{ $totalPatients }}</h3>
                    </div>
                </a>

                <div class="bg-white rounded-[1.5rem] p-5 shadow-sm border border-slate-200 flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center border border-emerald-200/50 shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-3-3v6m8-3a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Check-ups Today</p>
                        <h3 class="text-3xl font-extrabold text-slate-800">{{ $todayCheckups }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-[1.5rem] p-5 shadow-sm border border-slate-200 flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-amber-50 to-amber-100 text-amber-600 rounded-2xl flex items-center justify-center border border-amber-200/50 shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Due for Check-up</p>
                        <h3 class="text-3xl font-extrabold text-slate-800">{{ $dueCount }}</h3>
                        <p class="text-[11px] text-slate-400 font-medium">No check-up in 90 days</p>
                    </div>
                </div>

                <a href="{{ route('pharmacist.quickScan') }}" class="bg-white rounded-[1.5rem] p-5 shadow-sm hover:shadow-md border border-slate-200 transition-all flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-teal-50 to-teal-100 text-teal-600 rounded-2xl flex items-center justify-center border border-teal-200/50 shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 8V6a2 2 0 012-2h2M4 16v2a2 2 0 002 2h2m8-16h2a2 2 0 012 2v2m-4 12h2a2 2 0 002-2v-2M9 10h.01M15 10h.01M9.5 15a3.5 3.5 0 005 0"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Faces Registered</p>
                        <h3 class="text-3xl font-extrabold text-slate-800">{{ $facesRegistered }}<span class="text-base text-slate-400 font-bold"> / {{ $totalPatients }}</span></h3>
                        <p class="text-[11px] font-medium {{ $facesPending > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                            {{ $facesPending > 0 ? $facesPending . ' pending — not findable by Quick Scan' : 'All patients can use Quick Scan' }}
                        </p>
                    </div>
                </a>
            </div>

            <!-- Main Content Area -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Left Column: Patient Table -->
                <div class="lg:col-span-2 bg-white border border-slate-200 rounded-[2rem] p-7 shadow-sm">
                    <div class="flex justify-between items-center mb-6 gap-3">
                        <h3 class="font-extrabold text-xl text-slate-800 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                            </div>
                            Recently Added Patients
                        </h3>
                        <a href="{{ route('pharmacist.patients.index') }}" class="text-sm text-teal-600 hover:text-teal-800 font-bold px-4 py-2 bg-teal-50 hover:bg-teal-100 rounded-lg transition-colors whitespace-nowrap">View All</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left border-separate border-spacing-y-2">
                            <thead class="text-slate-500 font-bold uppercase tracking-wider text-xs">
                                <tr>
                                    <th class="py-3 px-4">Patient</th>
                                    <th class="py-3 px-4">BMI</th>
                                    <th class="py-3 px-4">Last Check-up</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPatients as $pt)
                                @php [$bmiText, $bmiClass, $bmiDot, $bmiLabel] = $bmiBadge($pt->bmi); @endphp
                                <tr class="bg-slate-50 hover:bg-teal-50/50 transition-colors group">
                                    <td class="py-4 px-4 font-bold text-slate-800 rounded-l-2xl border-y border-l border-slate-100">
                                        <div class="flex items-center gap-3">
                                            <span class="w-10 h-10 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-sm font-extrabold shrink-0">
                                                {{ strtoupper(mb_substr($pt->user->name ?? '?', 0, 1)) }}
                                            </span>
                                            <span>
                                                <span class="block text-base">{{ $pt->user->name ?? 'Unknown' }}</span>
                                                <span class="block text-xs text-slate-400 font-medium mt-0.5">
                                                    {{ $pt->gender }}, {{ $pt->age }}y · ID {{ $pt->id }}
                                                    @if(blank($pt->face_descriptor))
                                                        · <span class="text-amber-600">no face</span>
                                                    @endif
                                                </span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 border-y border-slate-100">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1.5 {{ $bmiClass }}" title="{{ $bmiLabel }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $bmiDot }}"></span>
                                            {{ $bmiText }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 border-y border-slate-100 text-slate-600 font-medium text-xs">
                                        {{ $pt->latestCheckup ? \Carbon\Carbon::parse($pt->latestCheckup->checkup_date)->format('d M Y') : 'None yet' }}
                                    </td>
                                    <td class="py-4 px-4 text-right rounded-r-2xl border-y border-r border-slate-100 whitespace-nowrap">
                                        <a href="{{ route('pharmacist.checkups.create', $pt->id) }}" class="inline-flex items-center px-3 py-2 bg-teal-600 text-white hover:bg-teal-700 font-bold rounded-xl text-xs shadow-sm">
                                            + Check-up
                                        </a>
                                        <a href="{{ route('pharmacist.patients.show', $pt->id) }}" class="inline-flex items-center px-3 py-2 bg-white text-slate-700 hover:text-teal-700 font-bold rounded-xl text-xs border border-slate-200 shadow-sm hover:border-teal-200">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-slate-400 font-medium bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                        No patients registered yet.
                                        <a href="{{ route('pharmacist.patients.create') }}" class="text-teal-600 font-bold hover:underline">Register the first patient</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Column: Clinical Alerts -->
                <div class="bg-white border border-slate-200 rounded-[2rem] p-7 shadow-sm flex flex-col lg:max-h-[46rem]">
                    <h3 class="font-extrabold text-xl text-slate-800 mb-1 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 relative">
                            @if($workflowAlerts > 0)
                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            </span>
                            @endif
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        Clinical Alerts
                        <span class="ml-auto rounded-full px-2.5 py-0.5 text-xs font-extrabold {{ $workflowAlerts > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $workflowAlerts }}</span>
                    </h3>
                    <p class="text-xs text-slate-400 font-medium mb-5">
                        {{ $healthAlertsAll->count() }} health · {{ $medicationAlertCount }} medication · {{ $adherenceAlertsAll->count() }} adherence · {{ $refillAlertsAll->count() }} refill · {{ $reviewAlertsAll->count() }} to review · {{ $dueCount }} check-up due
                        @if($shownAlerts < $workflowAlerts) — showing {{ $shownAlerts }} @endif
                    </p>

                    <div class="space-y-4 flex-1 overflow-y-auto pr-2 custom-scrollbar">

                        @if($workflowAlerts === 0)
                        <div class="p-8 border border-emerald-100 bg-emerald-50/50 rounded-2xl text-center shadow-sm">
                            <div class="w-12 h-12 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-sm font-bold text-emerald-800">All Clear!</p>
                            <p class="text-xs text-emerald-600 mt-1 font-medium">No alerts need your attention.</p>
                        </div>
                        @endif

                        <!-- Health Alerts (latest check-up per patient) -->
                        @foreach($healthAlerts as $alert)
                        <div class="p-4 border border-rose-100 bg-rose-50/50 rounded-2xl group hover:bg-rose-50 transition-colors shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold text-sm text-slate-800">{{ $alert->patient->user->name ?? 'Unknown' }}</h4>
                                <span class="px-2 py-1 bg-white rounded-md text-[10px] font-bold text-rose-500 shadow-sm border border-rose-100">{{ \Carbon\Carbon::parse($alert->checkup->checkup_date)->format('d M') }}</span>
                            </div>
                            <div class="text-xs text-slate-600 space-y-1.5 bg-white/50 p-2.5 rounded-xl border border-rose-100/50">
                                @foreach($alert->flags as [$label, $value, $tone])
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $tone === 'rose' ? 'bg-rose-500' : 'bg-amber-500' }}"></span>
                                        <span class="font-bold {{ $tone === 'rose' ? 'text-rose-700' : 'text-amber-700' }}">{{ $label }}:</span> {{ $value }}
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('pharmacist.patients.show', $alert->patient->id) }}" class="mt-3 text-xs font-bold text-rose-600 hover:text-rose-800 flex items-center gap-1 group-hover:gap-2 transition-all">
                                Review Case <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                        @endforeach

                        <!-- Medicines recorded by patients, not yet reviewed (with safety warnings) -->
                        @foreach($reviewAlerts as $alert)
                            @php $major = \App\Support\DrugSafety::hasMajor($alert->warnings); $med = $alert->med; @endphp
                            <div class="p-4 border {{ $major ? 'border-rose-300 bg-rose-50/70' : ($alert->warnings ? 'border-amber-200/60 bg-amber-50/50' : 'border-sky-200/60 bg-sky-50/50') }} rounded-2xl shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 {{ $major ? 'text-rose-500' : ($alert->warnings ? 'text-amber-500' : 'text-sky-500') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <h4 class="font-bold text-sm text-slate-800">{{ $med->patient->user->name ?? 'Unknown' }}</h4>
                                    <span class="ml-auto px-2 py-1 bg-white rounded-md text-[10px] font-bold shadow-sm border {{ $major ? 'text-rose-600 border-rose-100' : 'text-slate-500 border-slate-100' }}">{{ $major ? 'Safety warning' : 'Added by patient' }}</span>
                                </div>
                                <div class="text-xs font-medium bg-white/60 p-2.5 rounded-xl border border-slate-100 text-slate-700">
                                    <p><span class="font-bold">{{ $med->name }}</span> was recorded by the patient on {{ $med->created_at->format('d M') }}.</p>
                                    @foreach(array_slice($alert->warnings, 0, 2) as $w)
                                        <p class="mt-1 {{ $w['severity'] === 'major' ? 'text-rose-700 font-bold' : 'text-amber-800' }}">&#9888; {{ $w['title'] }}</p>
                                    @endforeach
                                    @if(count($alert->warnings) > 2)
                                        <p class="mt-1 text-slate-500">+ {{ count($alert->warnings) - 2 }} more</p>
                                    @endif
                                </div>
                                <div class="mt-3 flex items-center gap-3">
                                    <a href="{{ route('pharmacist.medication.index', $med->patient_id) }}" class="text-xs font-bold text-teal-600 hover:text-teal-800">Review medications &rarr;</a>
                                    @unless($alert->warnings)
                                        <form method="POST" action="{{ route('pharmacist.medication.review', $med->id) }}" class="ml-auto">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold text-slate-600 hover:text-slate-900 underline">Mark reviewed</button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        @endforeach

                        <!-- Refill Alerts (patient request or supply running out) -->
                        @foreach($refillAlerts as $med)
                            @php $out = $med->refillStatus() === 'overdue'; $days = $med->supplyDaysLeft(); @endphp
                            <div class="p-4 border {{ $out ? 'border-rose-200/60 bg-rose-50/50' : 'border-teal-200/60 bg-teal-50/50' }} rounded-2xl group shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 {{ $out ? 'text-rose-500' : 'text-teal-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <h4 class="font-bold text-sm text-slate-800">{{ $med->patient->user->name ?? 'Unknown' }}</h4>
                                    @if($med->refill_requested_at)
                                        <span class="ml-auto px-2 py-1 bg-white rounded-md text-[10px] font-bold text-teal-600 shadow-sm border border-teal-100">Requested</span>
                                    @endif
                                </div>
                                <p class="text-xs {{ $out ? 'text-rose-800' : 'text-teal-900' }} font-medium bg-white/60 p-2.5 rounded-xl border {{ $out ? 'border-rose-100/50' : 'border-teal-100/50' }}">
                                    <span class="font-bold">{{ $med->name }}:</span>
                                    @if($med->refill_requested_at)
                                        patient asked for a refill on {{ $med->refill_requested_at->format('d M') }}.
                                    @endif
                                    @if($days !== null)
                                        {{ $days < 0 ? 'Supply ran out ' . abs($days) . ' days ago.' : ($days === 0 ? 'Supply runs out today.' : "Supply runs out in {$days} " . \Illuminate\Support\Str::plural('day', $days) . '.') }}
                                    @endif
                                </p>
                                <a href="{{ route('pharmacist.medication.index', $med->patient_id) }}" class="mt-3 text-xs font-bold {{ $out ? 'text-rose-600 hover:text-rose-800' : 'text-teal-600 hover:text-teal-800' }} flex items-center gap-1 group-hover:gap-2 transition-all">
                                    Record Refill <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        @endforeach

                        <!-- Adherence Alerts (dose log, last 30 days) -->
                        @foreach($adherenceAlerts as $alert)
                            @php $poor = $alert->adherence['status'] === 'poor'; @endphp
                            <div class="p-4 border {{ $poor ? 'border-rose-200/60 bg-rose-50/50' : 'border-amber-200/60 bg-amber-50/50' }} rounded-2xl group shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 {{ $poor ? 'text-rose-500' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <h4 class="font-bold text-sm text-slate-800">{{ $alert->patient->user->name ?? 'Unknown' }}</h4>
                                    <span class="ml-auto px-2 py-1 bg-white rounded-md text-[10px] font-bold {{ $poor ? 'text-rose-600 border-rose-100' : 'text-amber-600 border-amber-100' }} shadow-sm border">{{ $alert->adherence['percent'] }}%</span>
                                </div>
                                <p class="text-xs {{ $poor ? 'text-rose-800' : 'text-amber-800' }} font-medium bg-white/60 p-2.5 rounded-xl border {{ $poor ? 'border-rose-100/50' : 'border-amber-100/50' }}">
                                    Adherence {{ strtolower($alert->adherence['label']) }} — {{ $alert->adherence['percent'] }}% of expected doses logged in the last {{ \App\Support\Adherence::WINDOW_DAYS }} days.
                                </p>
                                <a href="{{ route('pharmacist.medication.index', $alert->patient->id) }}" class="mt-3 text-xs font-bold {{ $poor ? 'text-rose-600 hover:text-rose-800' : 'text-amber-600 hover:text-amber-800' }} flex items-center gap-1 group-hover:gap-2 transition-all">
                                    Review Adherence <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        @endforeach

                        <!-- Medication Alerts -->
                        @foreach($medicationAlerts as $medication)
                            @php
                                $lastTaken = $medication->last_taken ? \Carbon\Carbon::parse($medication->last_taken) : null;
                                $endDate = $medication->end_date ? \Carbon\Carbon::parse($medication->end_date) : null;
                                $isEndingSoon = $endDate && ! $endDate->isPast() && today()->diffInDays($endDate) <= 7;
                            @endphp
                            <div class="p-4 border border-amber-200/60 bg-amber-50/50 rounded-2xl group shadow-sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002 2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <h4 class="font-bold text-sm text-slate-800">{{ $medication->patient->user->name ?? 'Unknown' }}</h4>
                                </div>
                                <p class="text-xs text-amber-800 font-medium bg-white/60 p-2.5 rounded-xl border border-amber-100/50">
                                    <span class="font-bold">{{ $medication->name }}:</span>
                                    @if($isEndingSoon)
                                        Treatment ends on {{ $endDate->format('d M Y') }}.
                                    @elseif($lastTaken)
                                        Last dose was {{ (int) $lastTaken->diffInDays(today()) }} days ago.
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
                            <div class="p-4 border border-teal-200/60 bg-teal-50/50 rounded-2xl group shadow-sm">
                                <h4 class="font-bold text-sm text-slate-800 mb-2">{{ $patientDue->user->name ?? 'Unknown' }}</h4>
                                <p class="text-xs text-teal-800 font-medium bg-white/60 p-2.5 rounded-xl border border-teal-100/50">
                                    {{ $patientDue->latestCheckup
                                        ? 'Last check-up: ' . \Carbon\Carbon::parse($patientDue->latestCheckup->checkup_date)->format('d M Y') . '.'
                                        : 'No health check-up on record.' }}
                                </p>
                                <div class="mt-3 grid gap-2">
                                    <a href="{{ route('pharmacist.checkups.create', $patientDue->id) }}" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-teal-600 border border-transparent text-white text-xs font-semibold rounded-lg shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-teal-500 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        Record Check-up
                                    </a>
                                    @if ($patientDue->user)
                                        <x-reminder-button :patient="$patientDue" size="sm" class="w-full" />
                                    @endif
                                </div>
                                @if ($patientDue->user)
                                    @php $sentAt = $lastReminderAt[$patientDue->user_id] ?? null; @endphp
                                    <p class="mt-2 text-[11px] font-medium text-slate-500">
                                        {{ $sentAt ? 'Last reminder sent ' . \Illuminate\Support\Carbon::parse($sentAt)->diffForHumans() : 'No reminder sent yet' }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
    </style>
</x-app-layout>
