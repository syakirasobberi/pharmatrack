<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="inline-flex items-center text-gray-500 hover:text-teal-600 font-bold mb-6 transition-colors">
                &larr; Back to Patient Profile
            </a>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

                {{-- Header --}}
                <div class="bg-teal-50/50 border-b border-gray-100 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-800">Record Health Check-up</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Patient: <strong class="text-teal-700">{{ $patient->user->name }}</strong>
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
                    <div class="mb-8 rounded-2xl border border-teal-100 bg-teal-50/60 p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-extrabold text-teal-950">Patient Measurements</h3>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Weight (kg)</label>
                                <input type="number" step="0.1" min="1" max="300" name="patient_weight" id="patient-weight"
                                    value="{{ old('patient_weight', $patient->weight) }}"
                                    oninput="updatePatientBmi(); generateAiAuto();"
                                    class="w-full rounded-xl border-teal-200 bg-white shadow-sm text-gray-700 font-bold focus:border-teal-500 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Height (cm)</label>
                                <input type="number" step="0.1" min="1" max="250" name="patient_height" id="patient-height"
                                    value="{{ old('patient_height', $patient->height) }}"
                                    oninput="updatePatientBmi(); generateAiAuto();"
                                    class="w-full rounded-xl border-teal-200 bg-white shadow-sm text-gray-700 font-bold focus:border-teal-500 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">BMI</label>
                                <input type="text" id="patient-bmi-display"
                                    value="{{ is_numeric(old('patient_weight', $patient->weight)) && is_numeric(old('patient_height', $patient->height)) && (float) old('patient_height', $patient->height) > 0 ? number_format((float) old('patient_weight', $patient->weight) / (((float) old('patient_height', $patient->height) / 100) ** 2), 1) : 'N/A' }}"
                                    readonly
                                    class="w-full rounded-xl border-teal-100 bg-white/80 shadow-sm text-gray-700 font-bold cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    {{-- Steps --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
                        <div class="rounded-2xl border border-teal-100 bg-teal-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-teal-700">Step 1</p>
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
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Report Source</label>
                            <select name="report_source" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 bg-gray-50">
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
                                @php($smartExtract = config('services.gemini.report_uploads'))
                                @if ($smartExtract)
                                <p class="text-sm text-cyan-800 mt-1">Upload a checkup report image or PDF. <strong>Extract Readings</strong> reads it on this computer (digital PDFs are read exactly). <strong>Smart Extract (AI)</strong> is better for phone photos and scans.</p>
                                <p class="text-xs text-cyan-700 mt-1">Smart Extract sends the report to Google Gemini; the file is not stored. Cover the patient's name and IC first if possible.</p>
                                @else
                                <p class="text-sm text-cyan-800 mt-1">Upload a checkup report image or PDF. <strong>Extract Readings</strong> reads it on this computer, so the report never leaves it (digital PDFs are read exactly).</p>
                                @endif
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                                <input type="file" id="ocr-image" accept="image/*,.pdf,application/pdf"
                                    class="block w-full text-sm text-cyan-900 file:mr-4 file:rounded-xl file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-bold file:text-cyan-800 hover:file:bg-cyan-100">
                                <button type="button" id="extract-ocr-btn"
                                    class="rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                                    Extract Readings
                                </button>
                                @if ($smartExtract)
                                <button type="button" id="ai-extract-btn"
                                    class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                                    Smart Extract (AI)
                                </button>
                                @endif
                            </div>
                        </div>

                        <div id="ocr-status" class="mt-4 text-sm font-semibold text-cyan-800">
                            Choose a report image or PDF, then click Extract Readings{{ $smartExtract ? ' or Smart Extract (AI)' : '' }}. Please review values before saving.
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
                            <summary class="cursor-pointer text-xs font-bold text-cyan-700">Show text read from the report</summary>
                            <pre id="ocr-output" class="mt-2 max-h-40 overflow-auto rounded-xl bg-white p-3 text-xs text-slate-600 border border-cyan-100 whitespace-pre-wrap"></pre>
                        </details>
                    </div>

                    {{-- Section: Core Readings --}}
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-5 bg-teal-500 rounded-full"></div>
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"></textarea>
                    </div>

                    {{-- AI Recommendation --}}
                    <div class="bg-teal-50 border border-teal-100 rounded-2xl p-5 mb-6">
                        <h3 class="font-bold text-teal-800 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            AI Health Insights
                        </h3>
                        <div id="aiResponseArea" class="text-sm text-teal-600 italic">
                            Upload a report or enter readings manually. The system will generate food, exercise, follow-up, and pharmacist review suggestions.
                        </div>
                        <p class="mt-3 text-xs font-semibold text-teal-700">
                            Medication notes are for pharmacist review only. The system does not automatically prescribe medication.
                        </p>
                    </div>

                    {{-- Action buttons --}}
                    <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                        <a href="{{ route('pharmacist.patients.show', $patient->id) }}"
                           class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl shadow-md transition-colors">
                            Save Reviewed Checkup
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="{{ asset('js/report-parser.js') }}?v={{ filemtime(public_path('js/report-parser.js')) }}"></script>
    <script>
    // ─── DOM refs ────────────────────────────────────────────────────────────────
    const ocrImageInput   = document.getElementById('ocr-image');
    const extractOcrButton = document.getElementById('extract-ocr-btn');
    const aiExtractButton  = document.getElementById('ai-extract-btn');
    const TRY_AI = aiExtractButton ? 'Try Smart Extract (AI), ' : 'Try ';
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

    // ─── Report readings → form fields ───────────────────────────────────────────
    const FIELD_INPUTS = {
        blood_pressure:             { input: 'bp-input',            badge: 'bp-badge',            detected: 'detected-bp',          label: 'BP' },
        blood_sugar:                { input: 'sugar-input',         badge: 'sugar-badge',         detected: 'detected-sugar',       label: 'blood sugar' },
        hba1c:                      { input: 'hba1c-input',         badge: 'hba1c-badge',         detected: 'detected-hba1c',       label: 'HbA1c' },
        haemoglobin:                { input: 'haemoglobin-input',   badge: 'haemoglobin-badge',   detected: 'detected-haemoglobin', label: 'haemoglobin' },
        cholesterol:                { input: 'cholesterol-input',   badge: 'chol-badge',          detected: 'detected-cholesterol', label: 'cholesterol' },
        ldl:                        { input: 'ldl-input',           badge: 'ldl-badge',           detected: null,                   label: 'LDL' },
        hdl:                        { input: 'hdl-input',           badge: 'hdl-badge',           detected: null,                   label: 'HDL' },
        albumin_globulin_ratio:     { input: 'ag-ratio-input',      badge: 'ag-ratio-badge',      detected: null,                   label: 'A/G ratio' },
        alkaline_phosphatase:       { input: 'alp-input',           badge: 'alp-badge',           detected: null,                   label: 'ALP' },
        aspartate_transaminase:     { input: 'ast-input',           badge: 'ast-badge',           detected: null,                   label: 'AST' },
        alanine_transaminase:       { input: 'alt-input',           badge: 'alt-badge',           detected: null,                   label: 'ALT' },
        gamma_glutamyl_transferase: { input: 'ggt-input',           badge: 'ggt-badge',           detected: 'detected-ggt',         label: 'GGT' },
        sodium:                     { input: 'sodium-input',        badge: 'sodium-badge',        detected: 'detected-sodium',      label: 'sodium' },
        renal_glucose:              { input: 'renal-glucose-input', badge: 'renal-glucose-badge', detected: null,                   label: 'urine glucose' },
        heart_rate:                 { input: 'heart-rate-input',    badge: 'hr-badge',            detected: 'detected-heart-rate',  label: 'heart rate' },
    };

    function setStatus(kind, message, notes = []) {
        const colors = { ok: 'text-green-700', warn: 'text-amber-700', error: 'text-red-600', info: 'text-cyan-800' };
        ocrStatus.replaceChildren();
        const main = document.createElement('span');
        main.className = colors[kind] || colors.info;
        main.textContent = message;
        ocrStatus.appendChild(main);
        if (notes.length) {
            const list = document.createElement('ul');
            list.className = 'mt-1 list-disc pl-5 text-xs font-normal text-cyan-700';
            notes.forEach((n) => { const li = document.createElement('li'); li.textContent = n; list.appendChild(li); });
            ocrStatus.appendChild(list);
        }
    }

    // Fill the fields that were found; fields not found are left as they are.
    function applyReadings(values, notes, source) {
        const found = [];
        for (const [key, cfg] of Object.entries(FIELD_INPUTS)) {
            const input = document.getElementById(cfg.input);
            const val = values[key];
            const detectedEl = cfg.detected ? document.getElementById(cfg.detected) : null;
            if (input && val !== undefined && val !== null && val !== '') {
                input.value = val;
                updateBadge(cfg.input, cfg.badge);
                if (detectedEl) detectedEl.textContent = val;
                found.push(cfg.label);
            } else if (detectedEl) {
                detectedEl.textContent = '-';
            }
        }

        const ts = document.getElementById('ocr-timestamp');
        if (ts) {
            ts.textContent = `Values extracted (${source}) on ${new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' })} — please verify before saving.`;
            ts.classList.remove('hidden');
        }

        if (found.length) {
            setStatus('ok', `✓ ${source}: filled ${found.join(', ')}. Please review before saving.`, notes);
            generateAiAuto();
        } else {
            setStatus('warn', `${source} finished, but no clear readings were found. ${TRY_AI}a clearer image, or enter values manually.`, notes);
        }
    }

    // ─── PDF helpers ──────────────────────────────────────────────────────────────
    async function loadPdf(file) {
        if (!window.pdfjsLib) throw new Error('PDF.js library is not ready. Please refresh the page.');
        return window.pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
    }

    // Digital PDFs carry their text: read it directly, keeping each table row on one line.
    async function pdfTextLayer(pdf, maxPages = 6) {
        let out = '';
        for (let n = 1; n <= Math.min(pdf.numPages, maxPages); n++) {
            const content = await (await pdf.getPage(n)).getTextContent();
            const items = content.items
                .filter((it) => it.str && it.str.trim() !== '')
                .map((it) => ({ str: it.str, x: it.transform[4], y: it.transform[5], h: Math.abs(it.transform[3]) || 8 }))
                .sort((a, b) => (b.y - a.y) || (a.x - b.x));
            const lines = [];
            for (const it of items) {
                const line = lines[lines.length - 1];
                if (line && Math.abs(line.y - it.y) <= Math.max(2, it.h * 0.4)) line.items.push(it);
                else lines.push({ y: it.y, items: [it] });
            }
            out += `\n--- Page ${n} ---\n` + lines
                .map((l) => l.items.sort((a, b) => a.x - b.x).map((i) => i.str).join(' '))
                .join('\n');
        }
        return out;
    }

    async function renderPdfPages(pdf, maxPages = 6) {
        const count = Math.min(pdf.numPages, maxPages);
        const pages = [];
        for (let n = 1; n <= count; n++) {
            setStatus('info', `Preparing PDF page ${n} of ${count}...`);
            const page     = await pdf.getPage(n);
            const viewport = page.getViewport({ scale: 2.5 });
            const canvas   = document.createElement('canvas');
            canvas.width   = viewport.width;
            canvas.height  = viewport.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            pages.push({ canvas: enhanceForOcr(canvas), pageNumber: n, pageCount: count });
        }
        return pages;
    }

    // ─── Image helpers ────────────────────────────────────────────────────────────
    function drawScaled(source, width, height, targetLong) {
        const scale = targetLong / Math.max(width, height);
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);
        const ctx = canvas.getContext('2d');
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
        return canvas;
    }

    // Grayscale + contrast stretch: helps Tesseract on dim or low-contrast photos.
    function enhanceForOcr(canvas) {
        const ctx = canvas.getContext('2d');
        const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const d = img.data;
        const hist = new Uint32Array(256);
        for (let i = 0; i < d.length; i += 4) {
            const g = Math.round(0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2]);
            d[i] = g; hist[g]++;
        }
        const total = d.length / 4;
        let lo = 0, hi = 255, acc = 0;
        for (let v = 0; v < 256; v++) { acc += hist[v]; if (acc > total * 0.02) { lo = v; break; } }
        acc = 0;
        for (let v = 255; v >= 0; v--) { acc += hist[v]; if (acc > total * 0.02) { hi = v; break; } }
        const range = Math.max(1, hi - lo);
        for (let i = 0; i < d.length; i += 4) {
            const g = Math.max(0, Math.min(255, ((d[i] - lo) * 255) / range));
            d[i] = d[i + 1] = d[i + 2] = g;
        }
        ctx.putImageData(img, 0, 0);
        return canvas;
    }

    async function imageForOcr(file) {
        const bitmap = await createImageBitmap(file);
        const long = Math.max(bitmap.width, bitmap.height);
        // Small photos are enlarged (Tesseract likes ~2000 px), huge ones shrunk to keep it fast.
        const target = Math.min(3000, Math.max(long, 2000));
        return enhanceForOcr(drawScaled(bitmap, bitmap.width, bitmap.height, target));
    }

    // ─── Tesseract (one worker reused for every page) ─────────────────────────────
    async function ocrCanvases(sources) {
        let label = '';
        const worker = await Tesseract.createWorker('eng', 1, {
            logger: (p) => {
                if (p.status === 'recognizing text') setStatus('info', `${label}: recognizing... ${Math.round(p.progress * 100)}%`);
            },
        });
        try {
            let text = '';
            for (const s of sources) {
                label = s.label;
                text += `\n--- ${s.label} ---\n` + ((await worker.recognize(s.canvas)).data.text || '');
            }
            return text;
        } finally {
            await worker.terminate();
        }
    }

    const isPdf = (file) => file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

    function setBusy(busy) {
        [extractOcrButton, aiExtractButton].forEach((b) => {
            if (!b) return;
            b.disabled = busy;
            b.classList.toggle('opacity-60', busy);
            b.classList.toggle('cursor-not-allowed', busy);
        });
    }

    function chosenFile() {
        const file = ocrImageInput.files[0];
        if (!file) setStatus('error', 'Please choose an image or PDF first.');
        return file;
    }

    // ─── B: Extract Readings (in the browser) ────────────────────────────────────
    if (extractOcrButton) {
        extractOcrButton.addEventListener('click', async () => {
            const file = chosenFile();
            if (!file) return;

            setBusy(true);
            setStatus('info', 'Reading report... this may take a few seconds.');
            ocrOutput.textContent = '';

            try {
                let rawText = '';
                let source = 'OCR';
                if (isPdf(file)) {
                    const pdf = await loadPdf(file);
                    const layer = await pdfTextLayer(pdf);
                    if (layer.replace(/--- Page \d+ ---|\s/g, '').length > 40 && /\d/.test(layer)) {
                        rawText = layer;
                        source = 'PDF text';
                    } else {
                        if (!window.Tesseract) throw new Error('OCR library is still loading.');
                        const pages = await renderPdfPages(pdf);
                        rawText = await ocrCanvases(pages.map((p) => ({ canvas: p.canvas, label: `Page ${p.pageNumber} of ${p.pageCount}` })));
                    }
                } else {
                    if (!window.Tesseract) throw new Error('OCR library is still loading.');
                    setStatus('info', 'Preparing image...');
                    rawText = await ocrCanvases([{ canvas: await imageForOcr(file), label: 'Image report' }]);
                }
                ocrOutput.textContent = rawText.trim() || 'No text detected.';
                const { values, notes } = window.ReportParser.parse(rawText);
                applyReadings(values, notes, source);
            } catch (err) {
                console.error('OCR Error:', err);
                setStatus('error', `Could not read the report. ${TRY_AI}a clearer image, or enter values manually.`);
            } finally {
                setBusy(false);
            }
        });
    }

    // ─── A: Smart Extract (AI) ────────────────────────────────────────────────────
    async function fileForAi(file) {
        if (isPdf(file)) return file;
        const bitmap = await createImageBitmap(file);
        const long = Math.max(bitmap.width, bitmap.height);
        const canvas = drawScaled(bitmap, bitmap.width, bitmap.height, Math.min(long, 2000));
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.88));
        return new File([blob], 'report.jpg', { type: 'image/jpeg' });
    }

    if (aiExtractButton) {
        aiExtractButton.addEventListener('click', async () => {
            const file = chosenFile();
            if (!file) return;

            setBusy(true);
            setStatus('info', 'Smart Extract: sending the report to the AI... this can take up to 30 seconds.');
            ocrOutput.textContent = '';

            try {
                const upload = await fileForAi(file);
                if (upload.size > 10 * 1024 * 1024) {
                    setStatus('error', 'The file is larger than 10 MB. Please upload a smaller PDF or a photo.');
                    return;
                }
                const form = new FormData();
                form.append('report', upload);
                const response = await fetch('{{ route("api.ai.extractReport") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: form,
                });
                const data = await response.json().catch(() => ({}));

                if (response.status === 429) {
                    setStatus('error', 'Too many Smart Extract requests. Please wait a minute and try again.');
                } else if (response.status === 413) {
                    setStatus('error', 'The file is too large for the server. Please upload a smaller file.');
                } else if (!response.ok || !data.success) {
                    const message = data.errors?.report?.[0] || data.message || 'Smart Extract failed. Please try again or use Extract Readings.';
                    setStatus('error', message);
                } else {
                    ocrOutput.textContent = JSON.stringify(data.values, null, 2);
                    applyReadings(data.values || {}, data.notes || [], 'Smart Extract (AI)');
                }
            } catch (err) {
                console.error('Smart Extract Error:', err);
                setStatus('error', 'Smart Extract failed. Please check your connection, or use Extract Readings.');
            } finally {
                setBusy(false);
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

        responseArea.innerHTML = '<span class="animate-pulse text-teal-600 font-bold">Analyzing health metrics...</span>';

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
                    exercise:           'border-teal-100 bg-teal-50 text-teal-900',
                    'follow-up':        'border-amber-100 bg-amber-50 text-amber-900',
                    'medication review':'border-purple-100 bg-purple-50 text-purple-900',
                };
                // AI text is untrusted: escape it before it goes into HTML.
                const escapeHtml = (value) => String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const cards = data.suggestion
                    .replace(/\*/g, '')
                    .split('\n')
                    .map(l => l.trim()).filter(Boolean)
                    .map(l => {
                        const cleaned = l.replace(/^\d+\.\s*/, '');
                        const [rawTitle, ...rest] = cleaned.split(':');
                        const title = rawTitle.trim();
                        const body  = rest.join(':').trim() || cleaned;
                        const style = styles[title.toLowerCase()] || 'border-teal-100 bg-white text-teal-900';
                        return `<div class="rounded-xl border ${style} p-4">
                            <p class="text-xs font-extrabold uppercase tracking-wide">${escapeHtml(title)}</p>
                            <p class="mt-1 text-sm font-medium leading-6">${escapeHtml(body)}</p>
                        </div>`;
                    }).join('');
                responseArea.innerHTML = `<div class="grid grid-cols-1 md:grid-cols-2 gap-3">${cards}</div>`;
            } else {
                if (suggestionInput) {
                    suggestionInput.value = '';
                }

                const errorLine = document.createElement('span');
                errorLine.className = 'text-red-500';
                errorLine.textContent = 'Error: ' + (data.message || 'Unable to generate suggestion.');
                responseArea.replaceChildren(errorLine);
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
