<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="inline-flex items-center text-gray-500 hover:text-blue-600 font-bold mb-6 transition-colors">
                &larr; Back to Patient Profile
            </a>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

                {{-- Header --}}
                <div class="bg-blue-50/50 border-b border-gray-100 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-800">Record Health Check-up</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Patient: <strong class="text-blue-700">{{ $patient->user->name }}</strong>
                            &nbsp;|&nbsp; BMI on file: <span id="patient-bmi" class="font-bold text-gray-700">{{ number_format($patient->bmi, 1) }}</span>
                        </p>
                    </div>
                    <div class="text-xs text-gray-400 text-right">
                        <p>Recorded by: <strong class="text-gray-600">{{ auth()->user()->name }}</strong></p>
                        <p id="current-datetime"></p>
                    </div>
                </div>

                <form action="{{ route('pharmacist.checkups.store', $patient->id) }}" method="POST" class="p-6 md:p-8">
                    @csrf
                    <input type="hidden" name="ai_suggestion" id="ai-suggestion-input" value="{{ old('ai_suggestion') }}">

                    @if(session('error'))
                        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <p class="font-bold">Please check the health check-up form.</p>
                            <ul class="mt-2 list-disc pl-5 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Patient table measurements --}}
                    <div class="mb-8 rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-extrabold text-blue-950">Patient Measurements</h3>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Weight (kg)</label>
                                <input type="number" step="0.1" min="1" max="300" name="patient_weight" id="patient-weight"
                                    value="{{ old('patient_weight', $patient->weight) }}"
                                    oninput="updatePatientBmi(); generateAiAuto();"
                                    class="w-full rounded-xl border-blue-200 bg-white shadow-sm text-gray-700 font-bold focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Height (cm)</label>
                                <input type="number" step="0.1" min="1" max="250" name="patient_height" id="patient-height"
                                    value="{{ old('patient_height', $patient->height) }}"
                                    oninput="updatePatientBmi(); generateAiAuto();"
                                    class="w-full rounded-xl border-blue-200 bg-white shadow-sm text-gray-700 font-bold focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">BMI</label>
                                <input type="text" id="patient-bmi-display"
                                    value="{{ is_numeric(old('patient_weight', $patient->weight)) && is_numeric(old('patient_height', $patient->height)) && (float) old('patient_height', $patient->height) > 0 ? number_format((float) old('patient_weight', $patient->weight) / (((float) old('patient_height', $patient->height) / 100) ** 2), 1) : 'N/A' }}"
                                    readonly
                                    class="w-full rounded-xl border-blue-100 bg-white/80 shadow-sm text-gray-700 font-bold cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    {{-- Steps --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Step 1</p>
                            <p class="mt-1 font-extrabold text-gray-900 text-sm">Upload full report</p>
                            <p class="mt-1 text-xs text-gray-500">Image or scanned PDF checkup result.</p>
                        </div>
                        <div class="rounded-2xl border border-cyan-100 bg-cyan-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-cyan-700">Step 2</p>
                            <p class="mt-1 font-extrabold text-gray-900 text-sm">Extract readings</p>
                            <p class="mt-1 text-xs text-gray-500">OCR reads all vitals and lab values.</p>
                        </div>
                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Step 3</p>
                            <p class="mt-1 font-extrabold text-gray-900 text-sm">Review values</p>
                            <p class="mt-1 text-xs text-gray-500">Pharmacist verifies before saving.</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Step 4</p>
                            <p class="mt-1 font-extrabold text-gray-900 text-sm">Generate advice</p>
                            <p class="mt-1 text-xs text-gray-500">Food, exercise, follow-up notes.</p>
                        </div>
                    </div>

                    {{-- Check-up Date + Report Source --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-100">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Check-up Date <span class="text-red-500">*</span></label>
                            <input type="date" name="checkup_date" value="{{ date('Y-m-d') }}" required
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Report Source</label>
                            <select name="report_source" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                                <option value="">— Select source —</option>
                                <option value="klinik_kesihatan">Klinik Kesihatan (KKM)</option>
                                <option value="private_clinic">Private Clinic</option>
                                <option value="hospital">Hospital</option>
                                <option value="private_lab">Private Lab</option>
                                <option value="home_device">Home Device / Self-measured</option>
                            </select>
                        </div>
                    </div>

                    {{-- OCR Panel --}}
                    <div class="bg-cyan-50 border border-cyan-100 rounded-2xl p-5 mb-8">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div>
                                <h3 class="font-extrabold text-cyan-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-cyan-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 2h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 5H19a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    OCR Autofill From Checkup Report
                                </h3>
                                <p class="text-sm text-cyan-800 mt-1">Upload a full checkup report image or PDF. The system will extract vitals and lab values and autofill the fields below.</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                                <input type="file" id="ocr-image" accept="image/*,.pdf,application/pdf"
                                    class="block w-full text-sm text-cyan-900 file:mr-4 file:rounded-xl file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-bold file:text-cyan-800 hover:file:bg-cyan-100">
                                <button type="button" id="extract-ocr-btn"
                                    class="rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                                    Extract Readings
                                </button>
                            </div>
                        </div>

                        <div id="ocr-status" class="mt-4 text-sm font-semibold text-cyan-800">
                            Choose a report image or PDF, then click Extract Readings. Please review values before saving.
                        </div>

                        {{-- Detected values preview --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-4" id="ocr-detected-grid">
                            <div class="rounded-xl bg-white border border-cyan-100 p-3">
                                <p class="text-xs font-bold uppercase text-cyan-700">BP</p>
                                <p id="detected-bp" class="mt-0.5 text-base font-extrabold text-slate-900">-</p>
                            </div>
                            <div class="rounded-xl bg-white border border-cyan-100 p-3">
                                <p class="text-xs font-bold uppercase text-cyan-700">Sugar</p>
                                <p id="detected-sugar" class="mt-0.5 text-base font-extrabold text-slate-900">-</p>
                            </div>
                            <div class="rounded-xl bg-white border border-cyan-100 p-3">
                                <p class="text-xs font-bold uppercase text-cyan-700">Cholesterol</p>
                                <p id="detected-cholesterol" class="mt-0.5 text-base font-extrabold text-slate-900">-</p>
                            </div>
                            <div class="rounded-xl bg-white border border-cyan-100 p-3">
                                <p class="text-xs font-bold uppercase text-cyan-700">Heart Rate</p>
                                <p id="detected-heart-rate" class="mt-0.5 text-base font-extrabold text-slate-900">-</p>
                            </div>
                            <div class="rounded-xl bg-white border border-cyan-100 p-3">
                                <p class="text-xs font-bold uppercase text-cyan-700">Haemoglobin</p>
                                <p id="detected-haemoglobin" class="mt-0.5 text-base font-extrabold text-slate-900">-</p>
                            </div>
                            <div class="rounded-xl bg-white border border-cyan-100 p-3">
                                <p class="text-xs font-bold uppercase text-cyan-700">HbA1c</p>
                                <p id="detected-hba1c" class="mt-0.5 text-base font-extrabold text-slate-900">-</p>
                            </div>
                            <div class="rounded-xl bg-white border border-cyan-100 p-3">
                                <p class="text-xs font-bold uppercase text-cyan-700">Sodium</p>
                                <p id="detected-sodium" class="mt-0.5 text-base font-extrabold text-slate-900">-</p>
                            </div>
                            <div class="rounded-xl bg-white border border-cyan-100 p-3">
                                <p class="text-xs font-bold uppercase text-cyan-700">GGT</p>
                                <p id="detected-ggt" class="mt-0.5 text-base font-extrabold text-slate-900">-</p>
                            </div>
                        </div>

                        <div id="ocr-timestamp" class="mt-2 text-xs text-cyan-600 italic hidden"></div>

                        <details class="mt-3">
                            <summary class="cursor-pointer text-xs font-bold text-cyan-700">Show raw OCR text</summary>
                            <pre id="ocr-output" class="mt-2 max-h-40 overflow-auto rounded-xl bg-white p-3 text-xs text-slate-600 border border-cyan-100 whitespace-pre-wrap"></pre>
                        </details>
                    </div>

                    {{-- Section: Core Readings --}}
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-5 bg-blue-500 rounded-full"></div>
                            <h3 class="font-extrabold text-gray-800 text-base">Core Readings</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Blood Pressure (mmHg)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: &lt;120/80</span>
                                </label>
                                <input type="text" name="blood_pressure" id="bp-input"
                                    oninput="updateBadge('bp-input','bp-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 120/80"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="bp-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Heart Rate (bpm)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: 60–100</span>
                                </label>
                                <input type="number" name="heart_rate" id="heart-rate-input"
                                    oninput="updateBadge('heart-rate-input','hr-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 75"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="hr-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Haemoglobin (g/dL)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: 12.0-16.0</span>
                                </label>
                                <input type="number" step="0.01" name="haemoglobin" id="haemoglobin-input"
                                    oninput="updateBadge('haemoglobin-input','haemoglobin-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 13.5"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="haemoglobin-badge" class="mt-1 text-xs font-bold"></p>
                            </div>


                        </div>
                    </div>

                    {{-- Section: Blood Glucose --}}
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-5 bg-amber-500 rounded-full"></div>
                            <h3 class="font-extrabold text-gray-800 text-base">Blood Glucose</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Fasting Blood Sugar (mmol/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: 3.9-6.0</span>
                                </label>
                                <input type="number" step="0.01" name="blood_sugar" id="sugar-input"
                                    oninput="updateBadge('sugar-input','sugar-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 5.5"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="sugar-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    HbA1c (%)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: &lt;5.7%</span>
                                </label>
                                <input type="number" step="0.1" name="hba1c" id="hba1c-input"
                                    oninput="updateBadge('hba1c-input','hba1c-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 5.4"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="hba1c-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                        </div>
                    </div>

                    {{-- Section: Liver Function Test --}}
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                            <h3 class="font-extrabold text-gray-800 text-base">Liver Function Test</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Albumin-Globulin Ratio
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: 1.1-2.5</span>
                                </label>
                                <input type="number" step="0.01" name="albumin_globulin_ratio" id="ag-ratio-input"
                                    oninput="updateBadge('ag-ratio-input','ag-ratio-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 1.4"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="ag-ratio-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Alkaline Phosphatase (U/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: 38-124</span>
                                </label>
                                <input type="number" step="0.01" name="alkaline_phosphatase" id="alp-input"
                                    oninput="updateBadge('alp-input','alp-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 85"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="alp-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Aspartate Transaminase (U/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: &lt;34</span>
                                </label>
                                <input type="number" step="0.01" name="aspartate_transaminase" id="ast-input"
                                    oninput="updateBadge('ast-input','ast-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 25"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="ast-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Alanine Transaminase (U/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: 10-49</span>
                                </label>
                                <input type="number" step="0.01" name="alanine_transaminase" id="alt-input"
                                    oninput="updateBadge('alt-input','alt-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 28"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="alt-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Gamma Glutamyl Transferase (U/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: &lt;38</span>
                                </label>
                                <input type="number" step="0.01" name="gamma_glutamyl_transferase" id="ggt-input"
                                    oninput="updateBadge('ggt-input','ggt-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 35"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="ggt-badge" class="mt-1 text-xs font-bold"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Section: Renal Function Profile --}}
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-5 bg-pink-500 rounded-full"></div>
                            <h3 class="font-extrabold text-gray-800 text-base">Renal Function Profile</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Sodium (mmol/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: 135-145</span>
                                </label>
                                <input type="number" step="0.01" name="sodium" id="sodium-input"
                                    oninput="updateBadge('sodium-input','sodium-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 140"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="sodium-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Glucose (mmol/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: 3.9-6.0</span>
                                </label>
                                <input type="number" step="0.01" name="renal_glucose" id="renal-glucose-input"
                                    oninput="updateBadge('renal-glucose-input','renal-glucose-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 5.1"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="renal-glucose-badge" class="mt-1 text-xs font-bold"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Section: Lipid Panel --}}
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
                            <h3 class="font-extrabold text-gray-800 text-base">Lipid Panel</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Total Cholesterol (mmol/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: &lt;5.2</span>
                                </label>
                                <input type="number" step="0.01" name="cholesterol" id="cholesterol-input"
                                    oninput="updateBadge('cholesterol-input','chol-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 4.8"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="chol-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    LDL (mmol/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: &lt;2.6</span>
                                </label>
                                <input type="number" step="0.01" name="ldl" id="ldl-input"
                                    oninput="updateBadge('ldl-input','ldl-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 2.6"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="ldl-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    HDL (mmol/L)
                                    <span class="ml-1 text-xs font-normal text-gray-400">Normal: &gt;1.3</span>
                                </label>
                                <input type="number" step="0.01" name="hdl" id="hdl-input"
                                    oninput="updateBadge('hdl-input','hdl-badge')" onblur="generateAiAuto()"
                                    placeholder="e.g. 1.3"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p id="hdl-badge" class="mt-1 text-xs font-bold"></p>
                            </div>

                        </div>
                    </div>

                    {{-- Section: Pharmacist Notes --}}
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-5 bg-gray-400 rounded-full"></div>
                            <h3 class="font-extrabold text-gray-800 text-base">Pharmacist Notes</h3>
                        </div>
                        <textarea name="notes" rows="3" placeholder="Optional — add any clinical observations, patient complaints, or medication notes here..."
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                    </div>

                    {{-- AI Recommendation --}}
                    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5 mb-6">
                        <h3 class="font-bold text-indigo-800 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            AI Health Insights
                        </h3>
                        <div id="aiResponseArea" class="text-sm text-indigo-600 italic">
                            Upload a report or enter readings manually. The system will generate food, exercise, follow-up, and pharmacist review suggestions.
                        </div>
                        <p class="mt-3 text-xs font-semibold text-indigo-700">
                            Medication notes are for pharmacist review only. The system does not automatically prescribe medication.
                        </p>
                    </div>

                    {{-- Action buttons --}}
                    <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                        <a href="{{ route('pharmacist.patients.show', $patient->id) }}"
                           class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-colors">
                            Save Reviewed Checkup
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script>
    // ─── DOM refs ────────────────────────────────────────────────────────────────
    const ocrImageInput   = document.getElementById('ocr-image');
    const extractOcrButton = document.getElementById('extract-ocr-btn');
    const ocrStatus       = document.getElementById('ocr-status');
    const ocrOutput       = document.getElementById('ocr-output');

    // ─── Live clock in header ────────────────────────────────────────────────────
    function updateClock() {
        const el = document.getElementById('current-datetime');
        if (el) el.textContent = new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
    }
    updateClock();
    setInterval(updateClock, 30000);

    // ─── PDF.js worker (must be set before any PDF is loaded) ───────────────────
    if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    // ─── Disable Extract button until Tesseract CDN has fully loaded ─────────────
    if (extractOcrButton) {
        extractOcrButton.disabled = true;
        extractOcrButton.title = 'OCR library is loading, please wait...';
    }
    window.addEventListener('load', () => {
        if (window.Tesseract && extractOcrButton) {
            extractOcrButton.disabled = false;
            extractOcrButton.title = '';
        }
    });

    // ─── Status badge helper ─────────────────────────────────────────────────────
    // Defines normal/borderline/high thresholds for each field.
    // Each entry: { low?, highBorder?, high?, lowBorder? }
    const THRESHOLDS = {
        'sugar-input':         { lowBorder: 3.9,  low: 3.0,  highBorder: 6.0, high: 7.0 },
        'haemoglobin-input':   { lowBorder: 12.0, low: 10.0, highBorder: 16.0, high: 18.0 },
        'hba1c-input':         { highBorder: 5.7,  high: 6.5  },
        'cholesterol-input':   { highBorder: 5.2,  high: 6.2  },
        'ldl-input':           { highBorder: 2.6,  high: 3.4  },
        'hdl-input':           { lowBorder: 1.3,   low: 1.0, reversed: true }, // higher = better
        'heart-rate-input':    { lowBorder: 60,    low: 50,   highBorder: 100, high: 110 },
        'ag-ratio-input':      { lowBorder: 1.1,   low: 0.8,  highBorder: 2.5,  high: 3.0 },
        'alp-input':           { lowBorder: 38,    low: 30,   highBorder: 124, high: 200 },
        'ast-input':           { highBorder: 34,   high: 80 },
        'alt-input':           { lowBorder: 10,    low: 5,    highBorder: 49,  high: 90 },
        'ggt-input':           { highBorder: 38,   high: 100 },
        'sodium-input':        { lowBorder: 135,   low: 130,  highBorder: 145,  high: 150 },
        'renal-glucose-input': { lowBorder: 3.9,   low: 3.0,  highBorder: 6.0, high: 7.0 },
    };

    function updateBadge(inputId, badgeId) {
        const input = document.getElementById(inputId);
        const badge = document.getElementById(badgeId);
        if (!input || !badge) return;

        // Special case: BP parsed differently
        if (inputId === 'bp-input') {
            updateBpBadge(input.value, badge);
            return;
        }

        const val = parseFloat(input.value);
        const t   = THRESHOLDS[inputId];
        if (!t || isNaN(val)) { badge.textContent = ''; return; }

        let label, color;

        if (t.reversed) {
            // For HDL: lower = worse
            if (val < (t.low ?? -Infinity))           { label = '⚠ Low';        color = 'text-red-600'; }
            else if (val < (t.lowBorder ?? -Infinity)) { label = '↓ Borderline'; color = 'text-amber-600'; }
            else                                       { label = '✓ Normal';      color = 'text-emerald-600'; }
        } else {
            if (val >= (t.high ?? Infinity))           { label = '⚠ High';        color = 'text-red-600'; }
            else if (val >= (t.highBorder ?? Infinity)){ label = '↑ Borderline';  color = 'text-amber-600'; }
            else if (val < (t.low ?? -Infinity))       { label = '⚠ Low';         color = 'text-red-600'; }
            else if (val < (t.lowBorder ?? -Infinity)) { label = '↓ Borderline';  color = 'text-amber-600'; }
            else                                       { label = '✓ Normal';       color = 'text-emerald-600'; }
        }

        badge.textContent = label;
        badge.className = `mt-1 text-xs font-bold ${color}`;
    }

    function updateBpBadge(value, badge) {
        const match = value.match(/(\d+)\s*\/\s*(\d+)/);
        if (!match) { badge.textContent = ''; return; }
        const sys = parseInt(match[1]), dia = parseInt(match[2]);
        let label, color;

        if (sys >= 140 || dia >= 90)       { label = '⚠ High (Stage 2)';     color = 'text-red-600'; }
        else if (sys >= 130 || dia >= 80)  { label = '↑ High (Stage 1)';     color = 'text-red-500'; }
        else if (sys >= 120)               { label = '↑ Elevated';            color = 'text-amber-600'; }
        else if (sys < 90 || dia < 60)     { label = '⚠ Low';                color = 'text-red-600'; }
        else                               { label = '✓ Normal';              color = 'text-emerald-600'; }

        badge.textContent = label;
        badge.className = `mt-1 text-xs font-bold ${color}`;
    }

    // ─── BMI auto-calculator ─────────────────────────────────────────────────────
    // ─── OCR text normalization ──────────────────────────────────────────────────
    function updatePatientBmi() {
        const weight = parseFloat(document.getElementById('patient-weight')?.value);
        const height = parseFloat(document.getElementById('patient-height')?.value);
        const bmiDisplay = document.getElementById('patient-bmi-display');
        const bmiHeader = document.getElementById('patient-bmi');

        if (weight > 0 && height > 0) {
            const bmi = weight / Math.pow(height / 100, 2);
            const display = bmi.toFixed(1);
            if (bmiDisplay) bmiDisplay.value = display;
            if (bmiHeader) bmiHeader.textContent = display;
        } else if (bmiDisplay) {
            bmiDisplay.value = 'N/A';
        }
    }

    updatePatientBmi();

    function normalizeOcrText(text) {
        return text
            .replace(/\r\n/g, '\n')
            .replace(/[|]/g, '1')
            .replace(/[，]/g, '.')
            .replace(/(\d)[,](\d)/g, '$1.$2')
            .replace(/[oO](?=\d)|(?<=\d)[oO]/g, '0')
            .replace(/\s+/g, ' ')
            .trim();
    }

    // ─── OCR parsers ─────────────────────────────────────────────────────────────
    function findBloodPressure(text) {
        const direct = text.match(/\b(8\d|9\d|1\d{2}|2[0-4]\d)\s*[\/\\]\s*(4\d|5\d|6\d|7\d|8\d|9\d|1[0-3]\d)\b/);
        if (direct) return `${direct[1]}/${direct[2]}`;

        const sys = text.match(/systolic[^0-9]{0,30}(8\d|9\d|1\d{2}|2[0-4]\d)/i);
        const dia = text.match(/diastolic[^0-9]{0,30}(4\d|5\d|6\d|7\d|8\d|9\d|1[0-3]\d)/i);
        if (sys && dia) return `${sys[1]}/${dia[1]}`;

        const bp = text.match(/(?:bp|blood\s*pressure)[^0-9]{0,30}(8\d|9\d|1\d{2}|2[0-4]\d)\s*[\/\\]\s*(4\d|5\d|6\d|7\d|8\d|9\d|1[0-3]\d)/i);
        if (bp) return `${bp[1]}/${bp[2]}`;

        return null;
    }

    function findNumberNearLabels(text, labels, min, max) {
        for (const label of labels) {
            const esc = label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const m = text.match(new RegExp(`${esc}[^0-9]{0,30}(\\d{1,3}(?:\\.\\d{1,2})?)`, 'i'));
            if (m) {
                const v = parseFloat(m[1]);
                if (v >= min && v <= max) return v.toFixed(2);
            }
        }
        return null;
    }

    function findLooseReading(text, min, max, ignoredValues = []) {
        const matches = [...text.matchAll(/\b\d{1,3}(?:\.\d{1,2})?\b/g)]
            .map(m => parseFloat(m[0]))
            .filter(v => v >= min && v <= max)
            .filter(v => !ignoredValues.some(ig => Math.abs(ig - v) < 0.01));
        return matches.length ? matches[0].toFixed(2) : null;
    }

    // ─── Autofill all fields from OCR text ───────────────────────────────────────
    function autofillFromOcr(rawText) {
        const text = normalizeOcrText(rawText);

        const bp = findBloodPressure(text);

        const sugar = findNumberNearLabels(text,
            ['fasting blood glucose','fasting glucose','blood glucose','blood sugar',
             'random glucose','random blood glucose','glucose','sugar','glu','fbs','rbs','bs'],
            2, 30)
            ?? findLooseReading(text, 2, 30, []);

        const hba1c = findNumberNearLabels(text,
            ['hba1c','hb a1c','glycated haemoglobin','glycated hemoglobin','a1c'],
            4, 15);

        const haemoglobin = findNumberNearLabels(text,
            ['haemoglobin','hemoglobin','hb'],
            5, 25);

        const cholesterol = findNumberNearLabels(text,
            ['total cholesterol','cholesterol total','serum cholesterol','cholesterol','chol','tc'],
            2, 20)
            ?? findLooseReading(text, 2, 20, sugar ? [parseFloat(sugar)] : []);

        const ldl = findNumberNearLabels(text,
            ['ldl cholesterol','ldl-c','ldl','low density lipoprotein','low-density lipoprotein'],
            0.5, 10);

        const hdl = findNumberNearLabels(text,
            ['hdl cholesterol','hdl-c','hdl','high density lipoprotein','high-density lipoprotein'],
            0.3, 5);

        const albuminGlobulinRatio = findNumberNearLabels(text,
            ['albumin globulin ratio','albumin-globulin ratio','a/g ratio','ag ratio'],
            0.2, 5);

        const alkalinePhosphatase = findNumberNearLabels(text,
            ['alkaline phosphatase','alk phosphatase','alp'],
            10, 1000);

        const aspartateTransaminase = findNumberNearLabels(text,
            ['aspartate transaminase','aspartate aminotransferase','ast','sgot'],
            1, 1000);

        const alanineTransaminase = findNumberNearLabels(text,
            ['alanine transaminase','alanine aminotransferase','alt','sgpt'],
            1, 1000);

        const gammaGlutamylTransferase = findNumberNearLabels(text,
            ['gamma glutamyl transferase','gamma-glutamyl transferase','gamma glutamyl','ggt'],
            1, 1000);

        const sodium = findNumberNearLabels(text,
            ['sodium','natrium','na'],
            80, 200);

        const renalGlucose = findNumberNearLabels(text,
            ['renal glucose','urine glucose','glucose'],
            0, 50);

        const heartRate = findNumberNearLabels(text,
            ['heart rate','pulse rate','pulse','hr','bpm'],
            30, 200);

        // Fill fields & update badges
        const fills = {
            'bp-input':             { val: bp,           detected: 'detected-bp',          badge: 'bp-badge' },
            'sugar-input':          { val: sugar,        detected: 'detected-sugar',        badge: 'sugar-badge' },
            'hba1c-input':          { val: hba1c,        detected: 'detected-hba1c',        badge: 'hba1c-badge' },
            'haemoglobin-input':    { val: haemoglobin,  detected: 'detected-haemoglobin',  badge: 'haemoglobin-badge' },
            'cholesterol-input':    { val: cholesterol,  detected: 'detected-cholesterol',  badge: 'chol-badge' },
            'ldl-input':            { val: ldl,          detected: 'detected-ldl',          badge: 'ldl-badge' },
            'hdl-input':            { val: hdl,          detected: 'detected-hdl',          badge: 'hdl-badge' },
            'ag-ratio-input':       { val: albuminGlobulinRatio, detected: null,             badge: 'ag-ratio-badge' },
            'alp-input':            { val: alkalinePhosphatase, detected: null,              badge: 'alp-badge' },
            'ast-input':            { val: aspartateTransaminase, detected: null,             badge: 'ast-badge' },
            'alt-input':            { val: alanineTransaminase, detected: null,               badge: 'alt-badge' },
            'ggt-input':            { val: gammaGlutamylTransferase, detected: 'detected-ggt', badge: 'ggt-badge' },
            'sodium-input':         { val: sodium,       detected: 'detected-sodium',        badge: 'sodium-badge' },
            'renal-glucose-input':  { val: renalGlucose, detected: null,                     badge: 'renal-glucose-badge' },
            'heart-rate-input':     { val: heartRate,    detected: 'detected-heart-rate',   badge: 'hr-badge' },
        };

        const found = [];
        for (const [inputId, { val, detected, badge }] of Object.entries(fills)) {
            const input = document.getElementById(inputId);
            if (input && val !== null) {
                input.value = val;
                if (badge) updateBadge(inputId, badge);
                if (detected) {
                    const el = document.getElementById(detected);
                    if (el) el.textContent = val;
                }
                found.push(inputId.replace('-input', '').replace(/-/g, ' '));
            } else if (detected) {
                const el = document.getElementById(detected);
                if (el) el.textContent = '-';
            }
        }

        // Timestamp
        const ts = document.getElementById('ocr-timestamp');
        if (ts) {
            ts.textContent = `Values extracted on ${new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' })} — please verify before saving.`;
            ts.classList.remove('hidden');
        }

        if (found.length) {
            ocrStatus.innerHTML = `<span class="text-green-700">✓ Autofilled: ${found.join(', ')}. Please review before saving.</span>`;
            generateAiAuto();
        } else {
            ocrStatus.innerHTML = '<span class="text-amber-700">OCR completed, but no clear readings were found. Please enter values manually.</span>';
        }
    }

    // ─── PDF rendering ────────────────────────────────────────────────────────────
    async function renderPdfPages(file, maxPages = 6) {
        if (!window.pdfjsLib) throw new Error('PDF.js library is not ready. Please refresh the page.');

        const data  = await file.arrayBuffer();
        const pdf   = await window.pdfjsLib.getDocument({ data }).promise;
        const count = Math.min(pdf.numPages, maxPages);
        const pages = [];

        for (let n = 1; n <= count; n++) {
            ocrStatus.textContent = `Preparing PDF page ${n} of ${count}...`;
            const page     = await pdf.getPage(n);
            const viewport = page.getViewport({ scale: 2 });
            const canvas   = document.createElement('canvas');
            canvas.width   = viewport.width;
            canvas.height  = viewport.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            pages.push({ canvas, pageNumber: n, pageCount: count });
        }
        return pages;
    }

    // ─── Tesseract recognition (v5 API) ──────────────────────────────────────────
    async function recognizeSource(source, label) {
        const worker = await Tesseract.createWorker('eng', 1, {
            logger: p => {
                if (p.status === 'recognizing text')
                    ocrStatus.textContent = `${label}: recognizing... ${Math.round(p.progress * 100)}%`;
            }
        });
        try {
            const result = await worker.recognize(source);
            return result.data.text || '';
        } finally {
            await worker.terminate();
        }
    }

    // ─── Extract button handler ───────────────────────────────────────────────────
    if (extractOcrButton) {
        extractOcrButton.addEventListener('click', async () => {
            const file = ocrImageInput.files[0];
            if (!file) {
                ocrStatus.innerHTML = '<span class="text-red-600">Please choose an image or PDF first.</span>';
                return;
            }
            if (!window.Tesseract) {
                ocrStatus.innerHTML = '<span class="text-red-600">OCR library is still loading. Please wait a moment and try again.</span>';
                return;
            }

            extractOcrButton.disabled = true;
            extractOcrButton.classList.add('opacity-60', 'cursor-not-allowed');
            ocrStatus.textContent = 'Reading report... this may take a few seconds.';
            ocrOutput.textContent = '';

            try {
                let rawText = '';
                if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                    const pages = await renderPdfPages(file);
                    for (const page of pages) {
                        rawText += `\n\n--- Page ${page.pageNumber} ---\n`;
                        rawText += await recognizeSource(page.canvas, `Page ${page.pageNumber} of ${page.pageCount}`);
                    }
                } else {
                    rawText = await recognizeSource(file, 'Image report');
                }
                ocrOutput.textContent = rawText.trim() || 'No text detected.';
                autofillFromOcr(rawText);
            } catch (err) {
                console.error('OCR Error:', err);
                ocrStatus.innerHTML = '<span class="text-red-600">OCR failed. Please try a clearer image or enter values manually.</span>';
            } finally {
                extractOcrButton.disabled = false;
                extractOcrButton.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        });
    }

    // ─── AI recommendation ────────────────────────────────────────────────────────
    function generateAiAuto() {
        const bmi          = document.getElementById('patient-bmi').innerText;
        const weight       = document.getElementById('patient-weight')?.value;
        const height       = document.getElementById('patient-height')?.value;
        const bp           = document.getElementById('bp-input').value;
        const sugar        = document.getElementById('sugar-input').value;
        const cholesterol  = document.getElementById('cholesterol-input').value;
        const hba1c        = document.getElementById('hba1c-input').value;
        const ldl          = document.getElementById('ldl-input').value;
        const hdl          = document.getElementById('hdl-input').value;
        const heartRate    = document.getElementById('heart-rate-input').value;
        const haemoglobin  = document.getElementById('haemoglobin-input').value;
        const sodium       = document.getElementById('sodium-input').value;
        const ggt          = document.getElementById('ggt-input').value;

        const responseArea = document.getElementById('aiResponseArea');
        const suggestionInput = document.getElementById('ai-suggestion-input');

        if (!bp && !sugar && !cholesterol && !hba1c && !ldl && !hdl && !heartRate && !haemoglobin && !sodium && !ggt) return;

        if (suggestionInput) {
            suggestionInput.value = '';
        }

        responseArea.innerHTML = '<span class="animate-pulse text-indigo-600 font-bold">Analyzing health metrics...</span>';

        const patientData = {
            bmi:          bmi          || 'Not specified',
            weight:       weight       || 'Not specified',
            height:       height       || 'Not specified',
            bp:           bp           || 'Not specified',
            sugar:        sugar        || 'Not specified',
            cholesterol:  cholesterol  || 'Not specified',
            hba1c:        hba1c        || 'Not specified',
            ldl:          ldl          || 'Not specified',
            hdl:          hdl          || 'Not specified',
            heart_rate:   heartRate    || 'Not specified',
            haemoglobin:  haemoglobin  || 'Not specified',
            sodium:       sodium       || 'Not specified',
            ggt:          ggt          || 'Not specified',
            _token: '{{ csrf_token() }}'
        };

        fetch('{{ route("api.ai.suggestion") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(patientData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (suggestionInput) {
                    suggestionInput.value = data.suggestion;
                }

                const styles = {
                    food:               'border-emerald-100 bg-emerald-50 text-emerald-900',
                    exercise:           'border-blue-100 bg-blue-50 text-blue-900',
                    'follow-up':        'border-amber-100 bg-amber-50 text-amber-900',
                    'medication review':'border-purple-100 bg-purple-50 text-purple-900',
                };
                const cards = data.suggestion
                    .replace(/\*/g, '')
                    .split('\n')
                    .map(l => l.trim()).filter(Boolean)
                    .map(l => {
                        const cleaned = l.replace(/^\d+\.\s*/, '');
                        const [rawTitle, ...rest] = cleaned.split(':');
                        const title = rawTitle.trim();
                        const body  = rest.join(':').trim() || cleaned;
                        const style = styles[title.toLowerCase()] || 'border-indigo-100 bg-white text-indigo-900';
                        return `<div class="rounded-xl border ${style} p-4">
                            <p class="text-xs font-extrabold uppercase tracking-wide">${title}</p>
                            <p class="mt-1 text-sm font-medium leading-6">${body}</p>
                        </div>`;
                    }).join('');
                responseArea.innerHTML = `<div class="grid grid-cols-1 md:grid-cols-2 gap-3">${cards}</div>`;
            } else {
                if (suggestionInput) {
                    suggestionInput.value = '';
                }

                responseArea.innerHTML = `<span class="text-red-500">Error: ${data.message}</span>`;
            }
        })
        .catch(() => {
            if (suggestionInput) {
                suggestionInput.value = '';
            }

            responseArea.innerHTML = '<span class="text-red-500">Failed to connect to AI server.</span>';
        });
    }
    </script>
</x-app-layout>
