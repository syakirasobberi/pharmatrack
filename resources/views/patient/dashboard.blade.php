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
                    $medicationAlerts = $patient->medications->filter(function ($medication) use ($today) {
                        if (! $medication->last_taken) {
                            return true;
                        }

                        return \Carbon\Carbon::parse($medication->last_taken)->diffInDays($today) > 7;
                    });
                    $endingSoonMedications = $patient->medications->filter(function ($medication) use ($today) {
                        if (! $medication->end_date) {
                            return false;
                        }

                        $endDate = \Carbon\Carbon::parse($medication->end_date);

                        return $endDate->isFuture() && $today->diffInDays($endDate) <= 7;
                    });
                @endphp

                <!-- Hero Section -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-3xl p-8 shadow-2xl text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
                    <!-- Decorative background elements -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-20 w-48 h-48 bg-purple-400 opacity-20 rounded-full blur-2xl"></div>
                    
                    <div class="relative z-10">
                        <p class="text-indigo-200 text-sm font-semibold tracking-wider uppercase mb-1">Welcome back</p>
                        <h1 class="text-4xl font-extrabold mb-2">{{ auth()->user()->name }}!</h1>
                        <p class="text-indigo-100 text-base">Here is a quick overview of your health records and active medications.</p>
                    </div>
                    <div class="relative z-10 flex gap-4">
                        <div class="bg-white/10 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/20 text-center">
                            <span class="block text-xs text-indigo-200 font-bold uppercase tracking-wider">Today's Date</span>
                            <span class="block text-lg font-extrabold">{{ now()->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>

                @if($medicationAlerts->isNotEmpty() || $endingSoonMedications->isNotEmpty())
                    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 shadow-sm">
                        <h3 class="font-extrabold text-lg text-amber-900 mb-4">Medication Reminders</h3>
                        <div class="space-y-3">
                            @foreach($medicationAlerts as $medication)
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 bg-white border border-amber-100 rounded-2xl p-4">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $medication->name }}</p>
                                        <p class="text-sm text-amber-800">
                                            {{ $medication->last_taken
                                                ? 'Last taken ' . \Carbon\Carbon::parse($medication->last_taken)->diffInDays($today) . ' days ago.'
                                                : 'No latest dose date has been recorded.' }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-bold text-amber-700 bg-amber-100 px-3 py-1 rounded-full">Follow up</span>
                                </div>
                            @endforeach

                            @foreach($endingSoonMedications as $medication)
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 bg-white border border-blue-100 rounded-2xl p-4">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $medication->name }}</p>
                                        <p class="text-sm text-blue-800">Treatment ends on {{ \Carbon\Carbon::parse($medication->end_date)->format('d M Y') }}.</p>
                                    </div>
                                    <span class="text-xs font-bold text-blue-700 bg-blue-100 px-3 py-1 rounded-full">Ending soon</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Health Vitals Quick Stats -->
                <h3 class="text-xl font-extrabold text-gray-800 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </span>
                    Your Core Vitals
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    {{-- Age --}}
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Age</p>
                        <h4 class="text-xl font-extrabold text-gray-800 mt-1">{{ $patient->age }} y/o</h4>
                    </div>

                    {{-- Gender --}}
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="8" r="4"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v10M9 19h6"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Gender</p>
                        <h4 class="text-xl font-extrabold text-gray-800 mt-1">{{ $patient->gender }}</h4>
                    </div>

                    {{-- Height --}}
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 21V3m0 0l3 3M8 3L5 6M16 3v18m0 0l-3-3m3 3l3-3"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Height</p>
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
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Weight</p>
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
                        <p class="text-xs font-bold uppercase opacity-80 tracking-wide">BMI Index</p>
                        <h4 class="text-2xl font-black mt-1">{{ number_format($patient->bmi, 1) }}</h4>
                    </div>
                </div>


                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Medications List -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-sm">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-extrabold text-2xl text-gray-800 flex items-center gap-2">
                                    💊 Active Medications
                                </h3>
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1 rounded-full">{{ $patient->medications->count() }} Prescriptions</span>
                            </div>

                            <div class="space-y-4">
                                @forelse($patient->medications as $med)
                                    <div class="flex items-start p-5 border border-gray-100 rounded-2xl hover:border-indigo-200 hover:bg-indigo-50/30 transition-all shadow-sm">
                                        <div class="flex-shrink-0 w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl shadow-inner mr-4">
                                            💊
                                        </div>
                                        <div class="flex-grow">
                                            <h4 class="text-lg font-bold text-gray-900">{{ $med->name }}</h4>
                                            <p class="text-sm text-gray-500 font-medium mt-1">Dosage: <span class="text-gray-800">{{ $med->dosage }}</span></p>
                                            <div class="mt-3 bg-gray-50 rounded-lg p-3 text-sm text-gray-700 border border-gray-100">
                                                <span class="font-bold text-gray-800 flex items-center gap-1"><svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Instructions:</span> 
                                                {{ $med->frequency ?: ($med->notes ?: 'Follow the dosage recorded by your pharmacist.') }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-8 border border-dashed border-gray-300 rounded-2xl text-center">
                                        <p class="text-gray-500 italic mb-2">No active medications recorded.</p>
                                        <p class="text-sm text-gray-400">If you were prescribed medications, please consult your pharmacist.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Medical History Summary -->
                        <div class="bg-gradient-to-r from-teal-50 to-emerald-50 border border-teal-100 rounded-3xl p-8 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-6 opacity-20 text-6xl">🏥</div>
                            <h3 class="font-extrabold text-xl text-teal-900 flex items-center gap-2 mb-6">
                                🏥 Medical History
                            </h3>
                            @if($patient->medicalHistory)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 relative z-10">
                                    <div class="bg-white/80 backdrop-blur rounded-2xl p-4 border border-teal-100 shadow-sm">
                                        <p class="text-xs text-teal-700 font-bold uppercase mb-1">Known Allergies</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ $patient->medicalHistory->allergies ?: 'None reported' }}</p>
                                    </div>
                                    <div class="bg-white/80 backdrop-blur rounded-2xl p-4 border border-teal-100 shadow-sm">
                                        <p class="text-xs text-teal-700 font-bold uppercase mb-1">Chronic Illnesses</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            Hypertension: {{ $patient->medicalHistory->hypertension ?: 'None' }}<br>
                                            Diabetes: {{ $patient->medicalHistory->diabetes ?: 'None' }}
                                        </p>
                                    </div>
                                    <div class="sm:col-span-2 bg-white/80 backdrop-blur rounded-2xl p-4 border border-teal-100 shadow-sm">
                                        <p class="text-xs text-teal-700 font-bold uppercase mb-1">Drug Allergies / Notes</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ $patient->medicalHistory->drug_allergies ?: 'None reported' }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="bg-white/60 p-6 rounded-2xl text-center border border-teal-100 border-dashed relative z-10">
                                    <p class="text-teal-800 font-medium">No medical history on file.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right Column: Checkups -->
                    <div class="space-y-6">
                        <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-sm">
                            <h3 class="font-extrabold text-xl text-gray-800 flex items-center gap-2 mb-6">
                                🩺 Recent Checkups
                            </h3>
                            <div class="space-y-5">
                                @forelse($patient->healthCheckups->take(3) as $checkup)
                                    <div class="relative pl-6 border-l-2 border-indigo-200 pb-2 last:pb-0 last:border-transparent">
                                        <div class="absolute w-3 h-3 bg-indigo-500 rounded-full -left-[7px] top-1 ring-4 ring-white"></div>
                                        <div class="mb-1 flex justify-between items-baseline">
                                            <h4 class="font-bold text-gray-800 text-sm">{{ \Carbon\Carbon::parse($checkup->checkup_date)->format('d M Y, h:i A') }}</h4>
                                        </div>
                                        <div class="bg-gray-50 rounded-xl p-4 mt-2 border border-gray-100 space-y-2">
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Blood Pressure</span>
                                                @php
                                                    $systolic = (float) preg_replace('/[^0-9.].*/', '', $checkup->blood_pressure);
                                                @endphp
                                                <span class="font-bold {{ $systolic > 130 ? 'text-red-600' : 'text-gray-800' }}">
                                                    {{ $checkup->blood_pressure ?: 'N/A' }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Blood Sugar</span>
                                                <span class="font-bold {{ $checkup->blood_sugar >= 5.6 ? 'text-red-600' : 'text-gray-800' }}">
                                                    {{ $checkup->blood_sugar }} mmol/L
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Cholesterol</span>
                                                <span class="font-bold {{ $checkup->cholesterol >= 5.2 ? 'text-orange-600' : 'text-gray-800' }}">
                                                    {{ $checkup->cholesterol }} mmol/L
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 bg-gray-50 rounded-2xl text-center text-gray-500 text-sm border border-gray-100">
                                        No recent checkup records available.
                                    </div>
                                @endforelse
                            </div>
                            
                            @if($patient->healthCheckups->count() > 3)
                                <div class="mt-6 text-center">
                                    <a href="{{ route('patient.checkups') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">View All Checkups &rarr;</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            @else
                <div class="bg-white rounded-3xl p-12 text-center shadow-lg border border-gray-100">
                    <div class="text-6xl mb-4">🤷‍♂️</div>
                    <h2 class="text-2xl font-extrabold text-gray-800 mb-2">No Patient Record Found</h2>
                    <p class="text-gray-500 mb-6">Your account is not linked to any patient records yet. Please visit the pharmacy counter to register your profile.</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
