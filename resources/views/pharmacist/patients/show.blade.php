<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('pharmacist.patients.index') }}" class="inline-flex items-center text-gray-500 hover:text-blue-700 font-bold transition-colors">
                    &larr; Back to Patient List
                </a>
            </div>

            @if(session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex flex-col md:flex-row items-start bg-white p-6 rounded-2xl shadow-sm hover:shadow-md border border-gray-200 transition-shadow duration-200 gap-6">
    <div class="flex-shrink-0 relative">
        <img
            src="https://ui-avatars.com/api/?name={{ urlencode($patient->user->name) }}&background=eff6ff&color=1d4ed8&size=128&font-size=0.35&bold=true"
            alt="{{ $patient->user->name }}'s Profile Photo"
            class="w-20 h-20 md:w-24 md:h-24 rounded-full object-cover shadow-sm ring-4 ring-gray-50"
        >
        <div class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full shadow-sm" title="Online"></div>
    </div>

    <div class="flex-1 w-full flex flex-col md:flex-row justify-between items-start gap-5">
        
        <div class="flex flex-col gap-2 min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 truncate tracking-tight">
                    {{ $patient->user->name }}
                </h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">
                    Normal
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-gray-500">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span>{{ $patient->gender }}, {{ $patient->age }} years old</span>
                </div>
                <span class="hidden sm:inline text-gray-300">&bull;</span>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <span class="truncate">{{ $patient->user->email }}</span>
                </div>
            </div>

            <div class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100 w-fit">
                <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                Assigned Pharmacist: {{ $patient->pharmacist?->name ?? 'Unassigned' }}
            </div>
        </div>

        <div class="w-full md:w-auto flex flex-col items-start md:items-end gap-3 pt-4 md:pt-0 border-t md:border-t-0 border-gray-100">
            <div class="flex flex-row w-full md:w-auto gap-3">
                <a href="{{ route('pharmacist.patients.edit', $patient->id) }}" class="flex-1 md:flex-none inline-flex justify-center items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 font-medium text-sm rounded-xl hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    Edit
                </a>
                
                <form action="{{ route('pharmacist.patients.sendReminder', $patient->id) }}" method="POST" class="flex-1 md:flex-none inline-flex">
                    @csrf
                    <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2 bg-indigo-600 border border-transparent text-white font-medium text-sm rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        Reminder
                    </button>
                </form>
            </div>
            
            <span class="text-xs text-gray-400 font-medium flex items-center gap-1 mt-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Last Update: {{ $patient->updated_at->format('d M Y, h:i A') }}
            </span>
        </div>
    </div>
</div>

            @php
                $latestCheckup  = $patient->healthCheckups->first();
                $allCheckups    = $patient->healthCheckups->sortByDesc('checkup_date');
                $medicalHistory = $patient->medicalHistory;
                $medications    = $patient->medications;
                $activeMedCount = $medications->count();

                $bmi = (float) $patient->bmi;
                if ($bmi >= 30)       [$bmiLabel, $bmiColor] = ['Obese',       'text-red-600 bg-red-50'];
                elseif ($bmi >= 25)   [$bmiLabel, $bmiColor] = ['Overweight',  'text-amber-600 bg-amber-50'];
                elseif ($bmi < 18.5)  [$bmiLabel, $bmiColor] = ['Underweight', 'text-yellow-600 bg-yellow-50'];
                else                  [$bmiLabel, $bmiColor] = ['Healthy',     'text-green-600 bg-green-50'];

                $alerts = collect();
                if (!$latestCheckup) {
                    $alerts->push(['No check-up recorded for this patient.', 'critical']);
                } elseif (\Carbon\Carbon::parse($latestCheckup->checkup_date)->diffInDays(now()) > 90) {
                    $alerts->push(['No check-up in the last 90 days. Review recommended.', 'review']);
                }
                if ($activeMedCount === 0) $alerts->push(['No active medications recorded.', 'review']);
                if ($activeMedCount >= 5)  $alerts->push(['Polypharmacy detected (' . $activeMedCount . ' meds). Review recommended.', 'review']);
                if (filled(optional($medicalHistory)->drug_allergies)) $alerts->push(['Drug allergies: ' . $medicalHistory->drug_allergies, 'critical']);
                if (filled(optional($medicalHistory)->allergies))      $alerts->push(['General allergies: ' . $medicalHistory->allergies, 'review']);
                if (filled(optional($medicalHistory)->others) && str_contains(strtolower(optional($medicalHistory)->others ?? ''), 'hypertension')) $alerts->push(['Hypertension noted: ' . $medicalHistory->others, 'critical']);
                elseif (filled(optional($medicalHistory)->others)) $alerts->push(['Other condition: ' . $medicalHistory->others, 'review']);
                if (filled(optional($medicalHistory)->diabetes) && optional($medicalHistory)->diabetes !== 'None') $alerts->push(['Diabetes: ' . $medicalHistory->diabetes, 'review']);
                if ($latestCheckup && (float)$latestCheckup->blood_sugar > 7.0)  $alerts->push(['Elevated blood sugar (' . $latestCheckup->blood_sugar . ' mmol/L).', 'critical']);
                if ($latestCheckup && (float)$latestCheckup->cholesterol > 5.2)  $alerts->push(['High cholesterol (' . $latestCheckup->cholesterol . ' mmol/L).', 'review']);
                if ($alerts->isEmpty()) $alerts->push(['No high-priority alerts. Patient is stable.', 'stable']);
            @endphp

            {{-- ── STAT TILES ── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center">
                    <span class="text-gray-500 text-sm font-bold mb-1">BMI</span>
                    <span class="text-3xl font-extrabold text-gray-800">{{ number_format($patient->bmi, 1) }}</span>
                    <span class="text-xs font-bold mt-1 px-2 py-0.5 rounded-full {{ $bmiColor }}">{{ $bmiLabel }}</span>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center">
                    <span class="text-gray-500 text-sm font-bold mb-1">Weight</span>
                    <span class="text-3xl font-extrabold text-gray-800">{{ $patient->weight }} <span class="text-lg text-gray-400">kg</span></span>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center">
                    <span class="text-gray-500 text-sm font-bold mb-1">Height</span>
                    <span class="text-3xl font-extrabold text-gray-800">{{ $patient->height }} <span class="text-lg text-gray-400">cm</span></span>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center">
                    <span class="text-gray-500 text-sm font-bold mb-1">Blood Pressure</span>
                    <span class="text-3xl font-extrabold text-gray-800">
                        {{ $latestCheckup ? $latestCheckup->blood_pressure : 'N/A' }}
                        <span class="text-lg text-gray-400">mmHg</span>
                    </span>
                </div>
            </div>

            {{-- ── CLINICAL SUMMARY PANEL ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Latest Vitals --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-extrabold text-base text-gray-800 mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                        Latest Vitals
                        @if($latestCheckup)
                            <span class="ml-auto text-xs font-medium text-gray-400">{{ \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') }}</span>
                        @endif
                    </h3>
                    @if($latestCheckup)
                        <dl class="space-y-2 text-sm">
                            @foreach([
                                ['Blood Pressure',   $latestCheckup->blood_pressure,  'mmHg'],
                                ['Heart Rate',       $latestCheckup->heart_rate,       'bpm'],
                                ['Haemoglobin',      $latestCheckup->haemoglobin,      'g/dL'],
                                ['Blood Sugar',      $latestCheckup->blood_sugar,      'mmol/L'],
                                ['HbA1c',            $latestCheckup->hba1c,            '%'],
                                ['A/G Ratio',        $latestCheckup->albumin_globulin_ratio, ''],
                                ['ALP',              $latestCheckup->alkaline_phosphatase, 'U/L'],
                                ['AST',              $latestCheckup->aspartate_transaminase, 'U/L'],
                                ['ALT',              $latestCheckup->alanine_transaminase, 'U/L'],
                                ['GGT',              $latestCheckup->gamma_glutamyl_transferase, 'U/L'],
                                ['Sodium',           $latestCheckup->sodium,           'mmol/L'],
                                ['Renal Glucose',    $latestCheckup->renal_glucose,    'mmol/L'],
                                ['Total Cholesterol',$latestCheckup->cholesterol,      'mmol/L'],
                                ['LDL',              $latestCheckup->ldl,              'mmol/L'],
                                ['HDL',              $latestCheckup->hdl,              'mmol/L'],
                            ] as [$label, $value, $unit])
                                @if($value !== null && $value !== '')
                                    <div class="flex justify-between border-b border-gray-50 pb-1">
                                        <dt class="font-medium text-gray-500">{{ $label }}</dt>
                                        <dd class="font-bold text-gray-800">{{ $value }}{{ $unit ? ' ' . $unit : '' }}</dd>
                                    </div>
                                @endif
                            @endforeach
                            @if($latestCheckup->notes)
                                <div class="pt-2">
                                    <dt class="font-medium text-gray-500 text-xs mb-1">Notes</dt>
                                    <dd class="text-gray-700 text-xs leading-relaxed bg-gray-50 rounded-lg p-2">{{ $latestCheckup->notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="text-sm text-gray-400 italic">No check-up recorded yet.</p>
                    @endif
                </div>

                {{-- Medical History Summary --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-extrabold text-base text-gray-800 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span>
                            Medical History
                        </h3>
                        <a href="{{ route('pharmacist.patients.medical.edit', $patient->id) }}" class="text-xs bg-purple-100 text-purple-700 hover:bg-purple-200 px-3 py-1.5 rounded-lg font-bold">+ Update</a>
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="border border-gray-200 rounded-xl p-3">
                            <p class="text-xs text-gray-500 font-bold mb-1">Diabetes</p>
                            <p class="text-sm font-bold text-gray-800">{{ optional($medicalHistory)->diabetes ?: 'None' }}</p>
                        </div>
                        <div class="border {{ optional($medicalHistory)->allergies ? 'border-red-200 bg-red-50' : 'border-gray-200' }} rounded-xl p-3">
                            <p class="text-xs {{ optional($medicalHistory)->allergies ? 'text-red-500' : 'text-gray-500' }} font-bold mb-1">Allergies</p>
                            <p class="text-sm font-bold {{ optional($medicalHistory)->allergies ? 'text-red-800' : 'text-gray-800' }}">{{ optional($medicalHistory)->allergies ?: 'No known allergies' }}</p>
                        </div>
                        <div class="border border-gray-200 rounded-xl p-3">
                            <p class="text-xs text-gray-500 font-bold mb-1">Drug Allergies</p>
                            <p class="text-sm font-bold text-gray-800">{{ optional($medicalHistory)->drug_allergies ?: 'No known drug allergies' }}</p>
                        </div>
                        <div class="border {{ optional($medicalHistory)->others ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200' }} rounded-xl p-3">
                            <p class="text-xs {{ optional($medicalHistory)->others ? 'text-indigo-600' : 'text-gray-500' }} font-bold mb-1">Other Conditions</p>
                            <p class="text-sm font-bold {{ optional($medicalHistory)->others ? 'text-indigo-800' : 'text-gray-800' }}">{{ optional($medicalHistory)->others ?: 'None recorded' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Clinical Alerts --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-extrabold text-base text-gray-800 mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>
                        Clinical Alerts
                        <span class="ml-auto text-xs font-medium text-gray-400">Rule-based</span>
                    </h3>
                    <div class="space-y-2">
                        @foreach($alerts as [$message, $severity])
                            @php
                                $aStyle = match($severity) {
                                    'critical' => 'bg-red-50 border-red-100 text-red-700',
                                    'review'   => 'bg-amber-50 border-amber-100 text-amber-700',
                                    default    => 'bg-emerald-50 border-emerald-100 text-emerald-700',
                                };
                                $aDot = match($severity) {
                                    'critical' => 'bg-red-400',
                                    'review'   => 'bg-amber-400',
                                    default    => 'bg-emerald-400',
                                };
                            @endphp
                            <div class="rounded-xl border px-3 py-2.5 text-xs font-semibold flex items-start gap-2 {{ $aStyle }}">
                                <span class="mt-1 w-2 h-2 rounded-full shrink-0 {{ $aDot }}"></span>
                                {{ $message }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="face-registration" class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h3 class="font-extrabold text-lg text-gray-800 mb-4 flex items-center gap-2">
                    Facial Biometrics Status
                </h3>

                @if($patient->face_descriptor)
                    <div class="p-4 bg-green-50 text-green-700 border border-green-200 rounded-xl font-bold">
                        Facial biometric data is registered. Patient is ready for Quick Scan.
                    </div>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <a href="#face-registration"
                           onclick="document.getElementById('registered-face-actions').classList.toggle('hidden'); return false;"
                           class="inline-flex justify-center px-5 py-2 bg-indigo-600 text-white font-bold text-sm rounded-full shadow hover:bg-indigo-700 transition-colors">
                            Update Face
                        </a>
                        <form action="{{ route('pharmacist.patients.deleteFace', $patient->id) }}" method="POST"
                              onsubmit="return confirm('Remove this patient face data? Quick Scan will no longer recognise this patient until a new face is registered.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex justify-center px-5 py-2 bg-red-50 text-red-700 border border-red-200 font-bold text-sm rounded-full hover:bg-red-100 transition-colors">
                                Remove Face
                            </button>
                        </form>
                    </div>
                    <div id="registered-face-actions" class="hidden mt-4">
                        <div class="p-4 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-xl mb-4 font-bold text-sm">
                            This will replace the current face data after patient consent.
                        </div>

                        <div class="flex flex-col items-center bg-gray-50 p-4 rounded-xl border border-dashed border-gray-300">
                            <div id="camera-idle" class="flex flex-col items-center gap-3 py-6">
                                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-gray-700">Camera is off</p>
                                <p class="text-xs text-gray-400 text-center max-w-xs">Click the button below to turn on the camera and update this patient's facial biometric.</p>
                                <button type="button" id="btn-start-camera"
                                    class="mt-2 px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-full shadow hover:bg-indigo-700 active:scale-95 transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.868v6.264a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                    </svg>
                                    Start Camera
                                </button>
                                <p id="model-loading-hint" class="text-xs text-gray-400 italic">Loading AI models in background...</p>
                            </div>

                            <div id="camera-active" class="hidden w-full flex flex-col items-center gap-3">
                                <p id="status" class="text-sm text-gray-500 font-bold">Initialising camera...</p>

                                <div id="video-container" class="relative inline-block w-full max-w-sm overflow-hidden rounded-xl shadow-sm bg-black">
                                    <video id="video" width="320" height="240" autoplay muted playsinline class="w-full bg-black rounded-xl"></video>
                                </div>

                                <div class="flex gap-3 mt-1">
                                    <button type="button" id="btn-update-face"
                                        class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-full shadow hover:bg-indigo-700 transition-colors flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Capture &amp; Save Face
                                    </button>
                                    <button type="button" id="btn-stop-camera"
                                        class="px-5 py-2 bg-gray-100 text-gray-600 font-bold rounded-full hover:bg-gray-200 transition-colors">
                                        Cancel
                                    </button>
                                </div>

                                <p id="update-success-msg" class="text-green-600 font-bold hidden mt-1">
                                    Face successfully saved. Reloading profile...
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-xl mb-4 font-bold text-sm">
                        No facial biometric found for this patient. Please scan now to update their profile.
                    </div>

                    <div class="flex flex-col items-center bg-gray-50 p-4 rounded-xl border border-dashed border-gray-300">

                        {{-- Idle prompt (shown before camera starts) --}}
                        <div id="camera-idle" class="flex flex-col items-center gap-3 py-6">
                            <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-700">Camera is off</p>
                            <p class="text-xs text-gray-400 text-center max-w-xs">Click the button below to turn on the camera and register this patient's facial biometric.</p>
                            <button type="button" id="btn-start-camera"
                                class="mt-2 px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-full shadow hover:bg-indigo-700 active:scale-95 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.868v6.264a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                </svg>
                                Start Camera
                            </button>
                            <p id="model-loading-hint" class="text-xs text-gray-400 italic">Loading AI models in background…</p>
                        </div>

                        {{-- Camera active panel (hidden until Start Camera clicked) --}}
                        <div id="camera-active" class="hidden w-full flex flex-col items-center gap-3">
                            <p id="status" class="text-sm text-gray-500 font-bold">Initialising camera…</p>

                            <div id="video-container" class="relative inline-block w-full max-w-sm overflow-hidden rounded-xl shadow-sm bg-black">
                                <video id="video" width="320" height="240" autoplay muted playsinline class="w-full bg-black rounded-xl"></video>
                            </div>

                            <div class="flex gap-3 mt-1">
                                <button type="button" id="btn-update-face"
                                    class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-full shadow hover:bg-indigo-700 transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Capture &amp; Save Face
                                </button>
                                <button type="button" id="btn-stop-camera"
                                    class="px-5 py-2 bg-gray-100 text-gray-600 font-bold rounded-full hover:bg-gray-200 transition-colors">
                                    Cancel
                                </button>
                            </div>

                            <p id="update-success-msg" class="text-green-600 font-bold hidden mt-1">
                                ✓ Face successfully saved. Reloading profile…
                            </p>
                        </div>

                    </div>
                @endif
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <div class="mb-4">
                    <h3 class="font-extrabold text-lg text-gray-800">Key Health Trend</h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        High-concern readings from recorded check-ups: blood sugar, cholesterol, HbA1c, and LDL when available.
                    </p>
                </div>
                @if($patient->healthCheckups->count() >= 2)
                    <div class="relative h-72 w-full">
                        <canvas id="healthChart"></canvas>
                    </div>
                @else
                    <div class="h-72 flex flex-col items-center justify-center text-center gap-2">
                        <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                        <p class="text-sm font-semibold text-gray-400">No historical check-up data available.</p>
                        <p class="text-xs text-gray-400">Complete at least 2 check-ups to view trends.</p>
                    </div>
                @endif
            </div>

            {{-- ── FULL CHECKUP HISTORY TABLE ── --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                    <div>
                        <h3 class="font-extrabold text-lg text-gray-800">Health Check-up History</h3>
                        <p class="text-xs text-gray-400 mt-0.5">All recorded check-up results for this patient.</p>
                    </div>
                    <a href="{{ route('pharmacist.checkups.create', $patient->id) }}" class="inline-flex justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-full shadow-md transition-colors">
                        + Add Check-up
                    </a>
                </div>

                @if($allCheckups->isEmpty())
                    <div class="py-10 text-center text-gray-400 italic text-sm">No check-up records found for this patient.</div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm text-left" style="min-width:1320px">
                            <thead class="bg-gray-50 text-gray-600 font-bold text-xs uppercase tracking-wide">
                                <tr>
                                    <th class="py-3 px-4 rounded-tl-xl">Date</th>
                                    <th class="py-3 px-4">BP <span class="font-normal normal-case text-gray-400">(mmHg)</span></th>
                                    <th class="py-3 px-4">Heart Rate <span class="font-normal normal-case text-gray-400">(bpm)</span></th>
                                    <th class="py-3 px-4">Haemoglobin <span class="font-normal normal-case text-gray-400">(g/dL)</span></th>
                                    <th class="py-3 px-4">Blood Sugar <span class="font-normal normal-case text-gray-400">(mmol/L)</span></th>
                                    <th class="py-3 px-4">HbA1c <span class="font-normal normal-case text-gray-400">(%)</span></th>
                                    <th class="py-3 px-4">A/G Ratio</th>
                                    <th class="py-3 px-4">ALP</th>
                                    <th class="py-3 px-4">AST</th>
                                    <th class="py-3 px-4">ALT</th>
                                    <th class="py-3 px-4">GGT</th>
                                    <th class="py-3 px-4">Sodium</th>
                                    <th class="py-3 px-4">Renal Glucose</th>
                                    <th class="py-3 px-4">Total Cholesterol <span class="font-normal normal-case text-gray-400">(mmol/L)</span></th>
                                    <th class="py-3 px-4">LDL</th>
                                    <th class="py-3 px-4">HDL</th>
                                    <th class="py-3 px-4 rounded-tr-xl">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($allCheckups as $checkup)
                                    <tr class="hover:bg-blue-50/30 transition-colors {{ $loop->first ? 'bg-blue-50/20' : '' }}">
                                        <td class="py-3 px-4 font-bold text-gray-800 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($checkup->checkup_date)->format('d M Y') }}
                                            @if($loop->first)
                                                <span class="ml-1 text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-bold">Latest</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 font-semibold">
                                            @if($checkup->blood_pressure)
                                                @php $sys = (int) explode('/', $checkup->blood_pressure)[0]; @endphp
                                                <span class="{{ $sys >= 140 ? 'text-red-600' : ($sys >= 130 ? 'text-amber-600' : 'text-gray-700') }}">{{ $checkup->blood_pressure }}</span>
                                            @else <span class="text-gray-300">—</span> @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->heart_rate ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->haemoglobin ?? '—' }}</td>
                                        <td class="py-3 px-4">
                                            @if($checkup->blood_sugar !== null)
                                                <span class="{{ (float)$checkup->blood_sugar > 7.0 ? 'text-red-600 font-bold' : 'text-gray-600' }}">{{ $checkup->blood_sugar }}</span>
                                            @else <span class="text-gray-300">—</span> @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->hba1c ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->albumin_globulin_ratio ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->alkaline_phosphatase ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->aspartate_transaminase ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->alanine_transaminase ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->gamma_glutamyl_transferase ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->sodium ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->renal_glucose ?? '—' }}</td>
                                        <td class="py-3 px-4">
                                            @if($checkup->cholesterol !== null)
                                                <span class="{{ (float)$checkup->cholesterol > 5.2 ? 'text-amber-600 font-bold' : 'text-gray-600' }}">{{ $checkup->cholesterol }}</span>
                                            @else <span class="text-gray-300">—</span> @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->ldl ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $checkup->hdl ?? '—' }}</td>
                                        <td class="py-3 px-4 text-gray-500 text-xs max-w-[140px] truncate">{{ $checkup->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-3">
                        {{ $allCheckups->count() }} record(s) total &nbsp;·&nbsp;
                        <span class="text-red-500 font-bold">■</span> High &nbsp;
                        <span class="text-amber-500 font-bold">■</span> Borderline &nbsp;
                        <span class="text-gray-400 font-bold">■</span> Normal
                    </p>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="font-extrabold text-lg text-gray-800 mb-6 flex items-center gap-2">Timeline</h3>
                        <div class="relative border-l-2 border-gray-200 ml-3 space-y-6">
                            @forelse($patient->healthCheckups as $checkup)
                                <div class="relative pl-6">
                                    <div class="absolute -left-[9px] top-1 w-4 h-4 bg-white border-2 border-blue-500 rounded-full"></div>
                                    <div class="text-xs font-bold text-gray-400 mb-1">{{ \Carbon\Carbon::parse($checkup->checkup_date)->format('d M Y') }}</div>
                                    <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50">
                                        <p class="font-bold text-sm text-gray-800">Health Check-up</p>
                                        <p class="text-xs text-gray-500 mt-1">BP: {{ $checkup->blood_pressure ?? '—' }} | Sugar: {{ $checkup->blood_sugar ?? '—' }} | Cholesterol: {{ $checkup->cholesterol ?? '—' }}</p>
                                        @if($checkup->notes)
                                            <p class="text-xs text-gray-400 mt-1 italic">{{ \Str::limit($checkup->notes, 80) }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="pl-6 text-sm text-gray-500 italic">No timeline records found.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="font-extrabold text-lg text-gray-800 flex items-center gap-2">Medication</h3>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('pharmacist.medication.index', $patient->id) }}" class="inline-flex justify-center text-xs bg-slate-100 text-slate-700 hover:bg-slate-200 px-3 py-1.5 rounded-lg font-bold">
                                    View All
                                </a>
                                <a href="{{ route('pharmacist.medication.index', $patient->id) }}" class="inline-flex justify-center text-xs bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg font-bold">
                                    + Add Medication
                                </a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 text-gray-700 font-bold">
                                    <tr>
                                        <th class="py-3 px-4 rounded-tl-lg">Medication Name</th>
                                        <th class="py-3 px-4">Dosage</th>
                                        <th class="py-3 px-4 rounded-tr-lg">Frequency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($patient->medications as $medication)
                                        <tr class="border-b last:border-0 hover:bg-gray-50">
                                            <td class="py-4 px-4 font-bold text-gray-800">{{ $medication->name }}</td>
                                            <td class="py-4 px-4 text-gray-600">{{ $medication->dosage }}</td>
                                            <td class="py-4 px-4 text-gray-600">{{ $medication->frequency ?? ($medication->notes ?: '-') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-6 px-4 text-center text-gray-500 italic">No medications prescribed yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl p-6 shadow-lg text-white" style="background: linear-gradient(135deg, #1d4ed8 0%, #0891b2 100%)">
                        <h3 class="font-extrabold text-lg mb-1">Full Clinical Summary</h3>
                        <p class="text-blue-200 text-sm mb-4">View the complete pharmacist clinical summary with charts, alerts, and medication overview.</p>
                        <a href="{{ route('pharmacist.patients.summary', $patient->id) }}"
                           class="inline-flex items-center gap-2 bg-white text-blue-700 font-bold text-sm px-5 py-2.5 rounded-full shadow hover:bg-blue-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            View Full Summary
                        </a>
                        <a href="{{ route('pharmacist.patients.summary.download', $patient->id) }}"
                           class="ml-2 inline-flex items-center gap-2 bg-blue-700/60 border border-blue-400/40 text-white font-bold text-sm px-5 py-2.5 rounded-full hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $chartData = $patient->healthCheckups->sortBy('checkup_date');
        $dates = $chartData->pluck('checkup_date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M y'))->values();
        $sugars = $chartData->pluck('blood_sugar')->values();
        $cholesterols = $chartData->pluck('cholesterol')->values();
        $hba1cSeries = $chartData->pluck('hba1c')->values();
        $ldlSeries = $chartData->pluck('ldl')->values();
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/face-api.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const healthChart = document.getElementById('healthChart');
            if (healthChart) {
                const ctx = healthChart.getContext('2d');
                const chartLabels = {!! json_encode($dates) !!};
                const sugarData = {!! json_encode($sugars) !!};
                const cholesterolData = {!! json_encode($cholesterols) !!};
                const hba1cData = {!! json_encode($hba1cSeries) !!};
                const ldlData = {!! json_encode($ldlSeries) !!};

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [
                            {
                                label: 'Blood Sugar (mmol/L)',
                                data: sugarData,
                                borderColor: 'rgb(37, 99, 235)',
                                backgroundColor: 'rgba(37, 99, 235, 0.10)',
                                borderWidth: 3,
                                pointBackgroundColor: 'rgb(37, 99, 235)',
                                fill: true,
                                tension: 0.35
                            },
                            {
                                label: 'Cholesterol (mmol/L)',
                                data: cholesterolData,
                                borderColor: 'rgb(20, 184, 166)',
                                backgroundColor: 'transparent',
                                borderWidth: 3,
                                pointBackgroundColor: 'rgb(20, 184, 166)',
                                fill: false,
                                tension: 0.35
                            },
                            {
                                label: 'HbA1c (%)',
                                data: hba1cData,
                                borderColor: 'rgb(147, 51, 234)',
                                backgroundColor: 'transparent',
                                borderWidth: 3,
                                pointBackgroundColor: 'rgb(147, 51, 234)',
                                fill: false,
                                tension: 0.35
                            },
                            {
                                label: 'LDL (mmol/L)',
                                data: ldlData,
                                borderColor: 'rgb(245, 158, 11)',
                                backgroundColor: 'transparent',
                                borderWidth: 3,
                                pointBackgroundColor: 'rgb(245, 158, 11)',
                                fill: false,
                                tension: 0.35
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } },
                        scales: { y: { beginAtZero: false } }
                    }
                });
            }

            const video      = document.getElementById('video');

            if (video) {
                const statusText    = document.getElementById('status');
                const btnUpdateFace = document.getElementById('btn-update-face');
                const btnStart      = document.getElementById('btn-start-camera');
                const btnStop       = document.getElementById('btn-stop-camera');
                const cameraIdle    = document.getElementById('camera-idle');
                const cameraActive  = document.getElementById('camera-active');
                const modelHint     = document.getElementById('model-loading-hint');
                const successMsg    = document.getElementById('update-success-msg');
                const patientId     = {{ $patient->id }};

                let modelsReady = false;
                let activeStream = null;

                // ── Load AI models silently in the background ─────────────────
                Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri('{{ asset("models") }}'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset("models") }}'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset("models") }}')
                ]).then(() => {
                    modelsReady = true;
                    if (modelHint) modelHint.textContent = 'AI models ready.';
                }).catch(err => {
                    if (modelHint) modelHint.textContent = 'Failed to load AI models.';
                    console.error('Model Load Error:', err);
                });

                // ── Start Camera button ───────────────────────────────────────
                btnStart.addEventListener('click', async () => {
                    if (!modelsReady) {
                        btnStart.textContent = 'Loading models… please wait';
                        btnStart.disabled = true;
                        await new Promise(resolve => {
                            const check = setInterval(() => {
                                if (modelsReady) { clearInterval(check); resolve(); }
                            }, 300);
                        });
                        btnStart.disabled = false;
                    }

                    cameraIdle.classList.add('hidden');
                    cameraActive.classList.remove('hidden');
                    statusText.textContent = 'Starting camera…';

                    try {
                        activeStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                        video.srcObject = activeStream;
                        statusText.textContent = 'Camera live — position patient\'s face in frame.';
                    } catch (e) {
                        statusText.textContent = 'Camera error. Please allow camera access.';
                    }
                });

                // ── Cancel / Stop Camera button ───────────────────────────────
                btnStop.addEventListener('click', () => {
                    if (activeStream) {
                        activeStream.getTracks().forEach(t => t.stop());
                        activeStream = null;
                    }
                    video.srcObject = null;
                    cameraActive.classList.add('hidden');
                    cameraIdle.classList.remove('hidden');
                });

                // ── Draw face detection overlay on play ───────────────────────
                video.addEventListener('play', () => {
                    // Remove any existing canvas overlay
                    const existing = document.getElementById('face-overlay-canvas');
                    if (existing) existing.remove();

                    const canvas = faceapi.createCanvasFromMedia(video);
                    canvas.id = 'face-overlay-canvas';
                    canvas.style.position = 'absolute';
                    canvas.style.top = '0';
                    canvas.style.left = '0';
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                    document.getElementById('video-container').append(canvas);

                    const displaySize = { width: video.videoWidth || video.width, height: video.videoHeight || video.height };
                    faceapi.matchDimensions(canvas, displaySize);

                    setInterval(async () => {
                        const detections = await faceapi.detectAllFaces(video);
                        const size = { width: video.videoWidth || video.width, height: video.videoHeight || video.height };
                        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                        if (detections && detections.length > 0) {
                            const resized = faceapi.resizeResults(detections, size);
                            faceapi.draw.drawDetections(canvas, resized);
                            statusText.textContent = `Face detected (${detections.length}). Click "Capture & Save Face" when ready.`;
                        } else {
                            statusText.textContent = 'No face detected — position patient in front of camera.';
                        }
                    }, 400);
                });

                // ── Capture & Save Face button ────────────────────────────────
                btnUpdateFace.addEventListener('click', async () => {
                    const detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();

                    if (detection) {
                        btnUpdateFace.textContent = 'Saving…';
                        btnUpdateFace.disabled = true;
                        btnStop.disabled = true;

                        const descriptorArray = Array.from(detection.descriptor);

                        fetch("{{ route('pharmacist.patients.updateFace') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ patient_id: patientId, descriptor: descriptorArray })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success') {
                                if (activeStream) activeStream.getTracks().forEach(t => t.stop());
                                successMsg.classList.remove('hidden');
                                successMsg.classList.add('block');
                                btnUpdateFace.classList.add('hidden');
                                btnStop.classList.add('hidden');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                alert('Failed to save face data. Please try again.');
                                btnUpdateFace.textContent = 'Capture & Save Face';
                                btnUpdateFace.disabled = false;
                                btnStop.disabled = false;
                            }
                        })
                        .catch(err => {
                            console.error('Save error:', err);
                            btnUpdateFace.textContent = 'Capture & Save Face';
                            btnUpdateFace.disabled = false;
                            btnStop.disabled = false;
                        });
                    } else {
                        alert('No face detected. Please make sure the patient\'s face is clearly visible.');
                    }
                });
            }
        });

    </script>
</x-app-layout>
