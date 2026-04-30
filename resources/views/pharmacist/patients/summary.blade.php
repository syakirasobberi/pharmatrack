<x-app-layout>
    @php
        $latestCheckup = $patient->healthCheckups->first();
        $medicalHistory = $patient->medicalHistory;
        $activeMedicationCount = $patient->medications->count();
        $bmi = (float) $patient->bmi;
        $patientName = $patient->user->name ?: ('Patient #' . $patient->id);
        $patientEmail = $patient->user->email ?: 'No email recorded';

        if ($bmi >= 30) {
            $bmiStatus = ['Obese', 'bg-red-100 text-red-700'];
        } elseif ($bmi >= 25) {
            $bmiStatus = ['Overweight', 'bg-amber-100 text-amber-700'];
        } elseif ($bmi < 18.5) {
            $bmiStatus = ['Underweight', 'bg-yellow-100 text-yellow-700'];
        } else {
            $bmiStatus = ['Healthy', 'bg-emerald-100 text-emerald-700'];
        }

        $riskFlags = collect([
            optional($medicalHistory)->hypertension === 'High Risk' ? 'High blood pressure risk' : null,
            filled(optional($medicalHistory)->diabetes) && optional($medicalHistory)->diabetes !== 'None' ? optional($medicalHistory)->diabetes : null,
            filled(optional($medicalHistory)->allergies) ? 'General allergies recorded' : null,
            filled(optional($medicalHistory)->drug_allergies) ? 'Drug allergies recorded' : null,
            $latestCheckup && filled($latestCheckup->blood_pressure) ? 'Latest BP: ' . $latestCheckup->blood_pressure : null,
        ])->filter()->values();
    @endphp

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('pharmacist.quickScan') }}" class="inline-flex items-center text-slate-500 hover:text-blue-700 font-bold transition-colors">
                    &larr; Back to Quick Scan
                </a>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-6 md:px-8 text-white" style="background: linear-gradient(90deg, #1d4ed8 0%, #0891b2 100%);">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">
                        <div class="flex items-center gap-4">
                            <img
                                src="https://ui-avatars.com/api/?name={{ urlencode($patientName) }}&background=ffffff&color=1d4ed8&size=128&font-size=0.35&bold=true"
                                alt="Patient avatar"
                                class="w-20 h-20 rounded-full border-4 border-white/60 shadow-sm"
                            >
                            <div>
                                <p class="text-sm font-bold uppercase tracking-[0.2em]" style="color: #dbeafe;">Health Summary</p>
                                <h1 class="text-3xl font-extrabold" style="color: #ffffff;">{{ $patientName }}</h1>
                                <div class="flex flex-wrap gap-3 text-sm mt-2" style="color: #eff6ff;">
                                    <span>{{ $patient->gender }}</span>
                                    <span>{{ $patient->age }} years</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="px-4 py-2 rounded-full bg-white text-blue-700 font-bold shadow-sm hover:bg-blue-50 transition-colors">
                                Full Profile
                            </a>
                            <a href="{{ route('pharmacist.patients.summary.download', $patient->id) }}" class="px-4 py-2 rounded-full bg-white text-blue-700 font-bold shadow-sm hover:bg-blue-50 transition-colors">
                                Download Summary
                            </a>
                            
                        </div>
                    </div>
                </div>

                <div class="p-6 md:p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">BMI Status</p>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-3xl font-extrabold text-slate-800">{{ number_format($patient->bmi, 1) }}</span>
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $bmiStatus[1] }}">{{ $bmiStatus[0] }}</span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Latest Blood Pressure</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-800">{{ $latestCheckup?->blood_pressure ?? 'N/A' }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $latestCheckup ? \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') : 'No check-up recorded yet' }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Blood Sugar</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-800">{{ $latestCheckup?->blood_sugar ?? 'N/A' }}</p>
                            <p class="mt-1 text-sm text-slate-500">Latest cholesterol: {{ $latestCheckup?->cholesterol ?? 'N/A' }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Active Medications</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-800">{{ $activeMedicationCount }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $patient->face_descriptor ? 'Face registered for quick scan' : 'Face registration needed' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="px-6 py-5 border-b border-slate-100">
                                <h2 class="text-lg font-extrabold text-slate-800">Clinical Snapshot</h2>
                                <p class="text-sm text-slate-500 mt-1">Fast view of the most important health information for this patient.</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-blue-600">Body Metrics</p>
                                    <div class="mt-3 space-y-2 text-sm text-slate-700">
                                        <p><strong>Height:</strong> {{ $patient->height }} cm</p>
                                        <p><strong>Weight:</strong> {{ $patient->weight }} kg</p>
                                        <p><strong>BMI:</strong> {{ number_format($patient->bmi, 1) }}</p>
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-600">Latest Check-up</p>
                                    <div class="mt-3 space-y-2 text-sm text-slate-700">
                                        <p><strong>Date:</strong> {{ $latestCheckup ? \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') : 'No data' }}</p>
                                        <p><strong>Blood pressure:</strong> {{ $latestCheckup?->blood_pressure ?? 'N/A' }}</p>
                                        <p><strong>Blood sugar:</strong> {{ $latestCheckup?->blood_sugar ?? 'N/A' }}</p>
                                        <p><strong>Cholesterol:</strong> {{ $latestCheckup?->cholesterol ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4 md:col-span-2">
                                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Medical History</p>
                                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-700">
                                        <p><strong>Hypertension:</strong> {{ optional($medicalHistory)->hypertension ?: 'None' }}</p>
                                        <p><strong>Diabetes:</strong> {{ optional($medicalHistory)->diabetes ?: 'None' }}</p>
                                        <p><strong>Allergies:</strong> {{ optional($medicalHistory)->allergies ?: 'No known allergies' }}</p>
                                        <p><strong>Drug allergies:</strong> {{ optional($medicalHistory)->drug_allergies ?: 'No known drug allergies' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                                <div class="px-6 py-5 border-b border-slate-100">
                                    <h2 class="text-lg font-extrabold text-slate-800">Alerts</h2>
                                </div>
                                <div class="p-6">
                                    @if($riskFlags->isNotEmpty())
                                        <div class="space-y-3">
                                            @foreach($riskFlags as $flag)
                                                <div class="rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm font-bold text-red-700">
                                                    {{ $flag }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 px-4 py-4 text-sm font-bold text-emerald-700">
                                            No high-priority alerts recorded right now.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                                <div class="px-6 py-5 border-b border-slate-100">
                                    <h2 class="text-lg font-extrabold text-slate-800">Medication Preview</h2>
                                </div>
                                <div class="p-6 space-y-3">
                                    @forelse($patient->medications->take(3) as $medication)
                                        <div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3">
                                            <p class="font-bold text-slate-800">{{ $medication->name }}</p>
                                            <p class="text-sm text-slate-500">{{ $medication->dosage }}{{ $medication->frequency ? ' • ' . $medication->frequency : '' }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500 italic">No active medications recorded.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
