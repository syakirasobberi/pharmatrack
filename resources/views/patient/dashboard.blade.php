<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Health Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            @if($patient)
                @php
                    $latestCheckup = $patient->healthCheckups->first();
                    $today = \Carbon\Carbon::today();
                    $checkupAlertMessage = null;

                    if (! $latestCheckup) {
                        $checkupAlertMessage = __('You have not had a health check-up yet. Visit the pharmacy counter and your pharmacist will do your first one.');
                    } elseif (\Carbon\Carbon::parse($latestCheckup->checkup_date)->lt($today->copy()->subDays(90))) {
                        $lastCheckup = \Carbon\Carbon::parse($latestCheckup->checkup_date);
                        $checkupAlertMessage = __('Your last check-up was :ago (:date). We recommend one every 3 months — visit the pharmacy counter when you can.', ['ago' => $lastCheckup->diffForHumans(), 'date' => $lastCheckup->translatedFormat('d M Y')]);
                    }

                    $hour = now()->hour;
                    $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
                    $displayName = \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower(auth()->user()->name));

                    $needsFaceRegistration = blank($patient->face_descriptor);

                    $medicationAlerts = $patient->currentMedications->filter(function ($medication) use ($today) {
                        if (! $medication->last_taken) {
                            return true;
                        }

                        return \Carbon\Carbon::parse($medication->last_taken)->diffInDays($today) > 7;
                    });
                    $endingSoonMedications = $patient->currentMedications->filter(function ($medication) use ($today) {
                        if (! $medication->end_date) {
                            return false;
                        }

                        $endDate = \Carbon\Carbon::parse($medication->end_date);

                        return $endDate->isFuture() && $today->diffInDays($endDate) <= 7;
                    });
                @endphp

                <!-- Hero Section -->
                <div class="bg-gradient-to-br from-teal-600 to-cyan-700 rounded-3xl p-6 sm:p-8 shadow-lg text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
                    <!-- Decorative background elements -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-20 w-48 h-48 bg-cyan-300 opacity-20 rounded-full blur-2xl"></div>

                    <div class="relative z-10">
                        <p class="text-teal-100 text-sm font-semibold mb-1">{{ $greeting }},</p>
                        <h1 class="text-2xl sm:text-3xl font-extrabold mb-2">{{ $displayName }}</h1>
                        <p class="text-teal-50 text-sm sm:text-base">{{ __('Your health records, medicines and check-ups in one place.') }}</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <a href="{{ route('patient.medications') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-teal-700 shadow-sm hover:bg-teal-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ __('Log today\'s medicine') }}
                            </a>
                            <a href="{{ route('patient.checkups') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-bold text-white ring-1 ring-white/30 hover:bg-white/25">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                {{ __('My check-up results') }}
                            </a>
                        </div>
                    </div>
                    <div class="relative z-10 flex gap-4">
                        <div class="bg-white/10 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/20 text-center">
                            <span class="block text-xs text-teal-200 font-bold uppercase tracking-wider">{{ __('Today\'s Date') }}</span>
                            <span class="block text-lg font-extrabold">{{ now()->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- System Notifications Hub -->
                @php
                    $unreadNotifications = auth()->user()->unreadNotifications;
                @endphp

                @if($unreadNotifications->isNotEmpty())
                    <div class="bg-teal-50/60 border border-teal-200/50 backdrop-blur-md rounded-3xl p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-lg text-teal-900 flex items-center gap-2">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-teal-500"></span>
                                </span>
                                {{ __('System Notifications (:count)', ['count' => $unreadNotifications->count()]) }}
                            </h3>
                            <a href="{{ route('patient.notifications.index') }}" class="text-xs font-extrabold uppercase tracking-wide text-teal-700 hover:text-teal-900">
                                {{ __('View Inbox') }}
                            </a>
                        </div>
                        <div class="space-y-3">
                            @foreach($unreadNotifications as $notification)
                                <div class="bg-white/80 border border-teal-100 rounded-2xl p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:shadow-md transition-shadow">
                                    <div>
                                        <p class="font-bold text-gray-900 text-base">
                                            {{ $notification->data['title'] ?? __('Notification') }}
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ $notification->data['message'] ?? '' }}
                                        </p>
                                        @if(isset($notification->data['last_checkup_date']) && $notification->data['last_checkup_date'])
                                            <p class="text-xs text-gray-400 mt-1 font-medium">
                                                {{ __('Last Check-up: :date', ['date' => \Carbon\Carbon::parse($notification->data['last_checkup_date'])->translatedFormat('d M Y')]) }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex w-full flex-col gap-2 sm:w-auto">
                                        @if(!empty($notification->data['action_url']))
                                            <a href="{{ $notification->data['action_url'] }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-xs font-bold text-teal-700 ring-1 ring-teal-200 hover:bg-teal-50">
                                                {{ __('Open') }}
                                            </a>
                                        @endif
                                        <form action="{{ route('patient.notifications.read', $notification->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700 transition-colors shadow-sm">
                                                {{ __('Mark as Read') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($needsFaceRegistration)
                    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 shadow-sm">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M4 6h8a3 3 0 013 3v6a3 3 0 01-3 3H4a2 2 0 01-2-2V8a2 2 0 012-2z"></path>
                                    </svg>
                                </span>
                                <div>
                                    <h3 class="font-extrabold text-lg text-amber-900">{{ __('Face Registration Needed') }}</h3>
                                    <p class="text-sm text-amber-800 mt-1">{{ __('Your face has not been registered for Quick Scan. Please ask your pharmacist to register your face at the pharmacy counter.') }}</p>
                                </div>
                            </div>
                            {{-- Opens the Contact Us panel (layouts/app) with the assigned pharmacist details. --}}
                            <button type="button" onclick="const c = document.getElementById('contact-us'); if (c) { c.open = true; c.querySelector('summary')?.focus(); }"
                                    class="inline-flex items-center justify-center rounded-xl bg-amber-100 px-4 py-2.5 text-sm font-bold text-amber-800 hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-300">
                                {{ __('Ask pharmacist') }}
                            </button>
                        </div>
                    </div>
                @endif

                @if($checkupAlertMessage)
                    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 shadow-sm">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </span>
                                <div>
                                    <h3 class="font-extrabold text-lg text-amber-900">{{ __('Time for a check-up') }}</h3>
                                    <p class="text-sm text-amber-800 mt-1">{{ $checkupAlertMessage }}</p>
                                </div>
                            </div>
                            <a href="{{ route('patient.checkups') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-amber-300 bg-white px-4 py-2.5 text-sm font-bold text-amber-800 hover:bg-amber-100">
                                {{ __('See my last results') }}
                            </a>
                        </div>
                    </div>
                @endif

                @if($medicationAlerts->isNotEmpty() || $endingSoonMedications->isNotEmpty())
                    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 shadow-sm">
                        <h3 class="font-extrabold text-lg text-amber-900 mb-4">{{ __('Medication Reminders') }}</h3>
                        <div class="space-y-3">
                            @foreach($medicationAlerts as $medication)
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 bg-white border border-amber-100 rounded-2xl p-4">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $medication->name }}</p>
                                        <p class="text-sm text-amber-800">
                                            {{ $medication->last_taken
                                                ? __('Last taken :days days ago.', ['days' => (int) \Carbon\Carbon::parse($medication->last_taken)->diffInDays($today)])
                                                : __('No latest dose date has been recorded.') }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-bold text-amber-700 bg-amber-100 px-3 py-1 rounded-full">{{ __('Follow up') }}</span>
                                </div>
                            @endforeach

                            @foreach($endingSoonMedications as $medication)
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 bg-white border border-teal-100 rounded-2xl p-4">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $medication->name }}</p>
                                        <p class="text-sm text-teal-800">{{ __('Treatment ends on :date.', ['date' => \Carbon\Carbon::parse($medication->end_date)->translatedFormat('d M Y')]) }}</p>
                                    </div>
                                    <span class="text-xs font-bold text-teal-700 bg-teal-100 px-3 py-1 rounded-full">{{ __('Ending soon') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Health Vitals Quick Stats -->
                <h3 class="text-xl font-extrabold text-gray-800 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-teal-100 text-teal-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </span>
                    {{ __('Your Core Vitals') }}
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    {{-- Age --}}
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-teal-50 text-teal-600 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">{{ __('Age') }}</p>
                        <h4 class="text-xl font-extrabold text-gray-800 mt-1">{{ __(':age y/o', ['age' => $patient->age]) }}</h4>
                    </div>

                    {{-- Gender --}}
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="8" r="4"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v10M9 19h6"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">{{ __('Gender') }}</p>
                        <h4 class="text-xl font-extrabold text-gray-800 mt-1">{{ __($patient->gender) }}</h4>
                    </div>

                    {{-- Height --}}
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 21V3m0 0l3 3M8 3L5 6M16 3v18m0 0l-3-3m3 3l3-3"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">{{ __('Height') }}</p>
                        <h4 class="text-xl font-extrabold text-gray-800 mt-1">{{ $patient->height }} cm</h4>
                    </div>

                    {{-- Weight --}}
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l9-3 9 3v2H3V6zM3 8h18v12a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">{{ __('Weight') }}</p>
                        <h4 class="text-xl font-extrabold text-gray-800 mt-1">{{ $patient->weight }} kg</h4>
                    </div>

                    {{-- BMI --}}
                    @php
                        $bmiColor = $patient->bmi >= 25 ? 'bg-orange-100 text-orange-700 border-orange-200' : 'bg-green-100 text-green-700 border-green-200';
                        $bmiIconColor = $patient->bmi >= 25 ? 'bg-orange-50 text-orange-600' : 'bg-green-50 text-green-600';
                    @endphp
                    <div class="bg-white rounded-2xl p-5 shadow-sm border {{ $bmiColor }} flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 {{ $bmiIconColor }} rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        @php
                            $bmiLabel = match (true) {
                                ! $patient->bmi => null,
                                $patient->bmi < 18.5 => __('Underweight'),
                                $patient->bmi < 25 => __('Healthy range'),
                                $patient->bmi < 30 => __('Overweight'),
                                default => __('Obese range'),
                            };
                        @endphp
                        <p class="text-xs font-bold uppercase opacity-80 tracking-wide">BMI</p>
                        <h4 class="text-2xl font-black mt-1">{{ number_format($patient->bmi, 1) }}</h4>
                        @if($bmiLabel)
                            <p class="mt-0.5 text-xs font-bold">{{ $bmiLabel }}</p>
                        @endif
                    </div>
                </div>


                <!-- Recent Checkups -->
                <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-sm">
                    <h3 class="font-extrabold text-xl text-gray-800 flex items-center gap-2">
                        {{ __('Recent Check-ups') }}
                    </h3>
                    <p class="mt-1 mb-5 text-sm text-gray-500">{{ __('Results recorded by your pharmacist. Only tests that were done are shown.') }}</p>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        @forelse($patient->healthCheckups->take(3) as $checkup)
                            @include('patient.partials.checkup-card', ['checkup' => $checkup, 'compact' => true])
                        @empty
                            <div class="lg:col-span-3 p-6 bg-gray-50 rounded-2xl text-center text-gray-500 text-sm border border-gray-100">
                                {{ __('No recent checkup records available.') }}
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6 text-center">
                        <a href="{{ route('patient.checkups') }}" class="text-sm font-bold text-teal-600 hover:text-teal-800">{{ __('View All Checkups') }} &rarr;</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8">
                    <!-- Medications List -->
                    <div class="space-y-6">
                        <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                                <h3 class="font-extrabold text-xl text-gray-800 flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-teal-100 text-teal-600">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 20.5l10-10a4.95 4.95 0 10-7-7l-10 10a4.95 4.95 0 107 7zM8.5 8.5l7 7" /></svg>
                                    </span>
                                    {{ __('Current Medications') }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="bg-teal-100 text-teal-800 text-xs font-bold px-3 py-1 rounded-full">{{ trans_choice(':count medicine|:count medicines', $patient->currentMedications->count(), ['count' => $patient->currentMedications->count()]) }}</span>
                                    <a href="{{ route('patient.medications') }}" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-teal-700">
                                        {{ __('Open My Medications') }}
                                    </a>
                                </div>
                            </div>

                            <div class="space-y-4">
                                @forelse($patient->currentMedications as $med)
                                    <div class="flex items-start p-5 border border-gray-100 rounded-2xl hover:border-teal-200 hover:bg-teal-50/30 transition-all shadow-sm">
                                        <div class="flex-shrink-0 w-12 h-12 bg-teal-100 text-teal-600 rounded-xl flex items-center justify-center mr-4">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 20.5l10-10a4.95 4.95 0 10-7-7l-10 10a4.95 4.95 0 107 7zM8.5 8.5l7 7" /></svg>
                                        </div>
                                        <div class="flex-grow">
                                            <h4 class="text-lg font-bold text-gray-900">{{ $med->name }}</h4>
                                            <p class="text-sm text-gray-500 font-medium mt-1">{{ __('Dosage:') }} <span class="text-gray-800">{{ $med->dosage }}</span></p>
                                            <div class="mt-3 bg-gray-50 rounded-lg p-3 text-sm text-gray-700 border border-gray-100">
                                                <span class="font-bold text-gray-800 flex items-center gap-1"><svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> {{ __('Instructions:') }}</span> 
                                                {{ $med->frequency ?: ($med->notes ?: __('Follow the dosage recorded by your pharmacist.')) }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-8 border border-dashed border-gray-300 rounded-2xl text-center">
                                        <p class="font-semibold text-gray-600 mb-1">{{ __('No current medications.') }}</p>
                                        <p class="text-sm text-gray-500">{!! __('Taking something your pharmacist doesn\'t know about? Add it on <a href=":url" class="font-bold text-teal-700 hover:underline">My Medications</a>.', ['url' => e(route('patient.medications'))]) !!}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Medical History Summary -->
                        <div class="bg-gradient-to-r from-teal-50 to-emerald-50 border border-teal-100 rounded-3xl p-8 shadow-sm relative overflow-hidden">
                            <h3 class="font-extrabold text-xl text-teal-900 flex items-center gap-2 mb-6">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white text-teal-600 ring-1 ring-teal-100">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                </span>
                                {{ __('Medical History') }}
                            </h3>
                            @if($patient->medicalHistory)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 relative z-10">
                                    <div class="bg-white/80 backdrop-blur rounded-2xl p-4 border border-teal-100 shadow-sm">
                                        <p class="text-xs text-teal-700 font-bold uppercase mb-1">{{ __('Known Allergies') }}</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ $patient->medicalHistory->allergies ?: __('None reported') }}</p>
                                    </div>
                                    <div class="bg-white/80 backdrop-blur rounded-2xl p-4 border border-teal-100 shadow-sm">
                                        <p class="text-xs text-teal-700 font-bold uppercase mb-1">{{ __('Chronic Illnesses') }}</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ __('Hypertension:') }} {{ $patient->medicalHistory->hypertension ? __($patient->medicalHistory->hypertension) : __('None') }}<br>
                                            {{ __('Diabetes:') }} {{ $patient->medicalHistory->diabetes ? __($patient->medicalHistory->diabetes) : __('None') }}
                                        </p>
                                    </div>
                                    <div class="sm:col-span-2 bg-white/80 backdrop-blur rounded-2xl p-4 border border-teal-100 shadow-sm">
                                        <p class="text-xs text-teal-700 font-bold uppercase mb-1">{{ __('Drug Allergies / Notes') }}</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ $patient->medicalHistory->drug_allergies ?: __('None reported') }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="bg-white/60 p-6 rounded-2xl text-center border border-teal-100 border-dashed relative z-10">
                                    <p class="text-teal-800 font-medium">{{ __('No medical history on file.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

            @else
                <div class="bg-white rounded-3xl p-12 text-center shadow-lg border border-gray-100">
                    <div class="text-6xl mb-4">🤷‍♂️</div>
                    <h2 class="text-2xl font-extrabold text-gray-800 mb-2">{{ __('No Patient Record Found') }}</h2>
                    <p class="text-gray-500 mb-6">{{ __('Your account is not linked to any patient records yet. Please visit the pharmacy counter to register your profile.') }}</p>
                </div>
            @endif

        </div>
    </div>

    {{-- Face registration is shown as the amber banner above; no blocking alert() popup. --}}
</x-app-layout>
