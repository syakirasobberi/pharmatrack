<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            
            <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="inline-flex items-center text-gray-500 hover:text-blue-600 font-bold mb-6 transition-colors">
                &larr; Back to Patient Profile
            </a>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="bg-blue-50/50 border-b border-gray-100 p-6">
                    <h2 class="text-2xl font-extrabold text-gray-800">Record Health Check-up</h2>
                    <p class="text-sm text-gray-500 mt-1">Patient: <strong class="text-blue-700">{{ $patient->user->name }}</strong> | BMI: <span id="patient-bmi">{{ number_format($patient->bmi, 1) }}</span></p>
                </div>

                <form action="{{ route('pharmacist.checkups.store', $patient->id) }}" method="POST" class="p-8">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="md:col-span-2 border-b border-gray-100 pb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Check-up Date <span class="text-red-500">*</span></label>
                            <input type="date" name="checkup_date" value="{{ date('Y-m-d') }}" required 
                                class="w-full md:w-1/2 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Blood Pressure (mmHg)</label>
                            <input type="text" name="blood_pressure" id="bp-input" onblur="generateAiAuto()" placeholder="e.g. 120/80" 
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Blood Sugar (mmol/L)</label>
                            <input type="number" step="0.01" name="blood_sugar" id="sugar-input" onblur="generateAiAuto()" placeholder="e.g. 5.5" 
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Cholesterol (mmol/L)</label>
                            <input type="number" step="0.01" name="cholesterol" id="cholesterol-input" onblur="generateAiAuto()" placeholder="e.g. 4.2" 
                                class="w-full md:w-1/2 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 mb-6">
                        <h3 class="font-bold text-indigo-800 flex items-center gap-2 mb-2">
                            ✨ Live AI Analysis
                        </h3>
                        <div id="aiResponseArea" class="text-sm text-indigo-600 italic">
                            Please enter the data above. Clinical suggestions will be generated automatically...
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                        <a href="{{ route('pharmacist.patients.show', $patient->id) }}" 
                           class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-colors">
                            Save Record
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
    function generateAiAuto() {
        // 1. Ambil nilai BMI yang statik dari Header atas (kerana pharmacist tak taip BMI)
        const bmiValue = document.getElementById('patient-bmi').innerText;
        
        // 2. Ambil nilai input yang sedang ditaip oleh Ahli Farmasi
        const bpValue = document.getElementById('bp-input').value;
        const sugarValue = document.getElementById('sugar-input').value;
        const cholesterolValue = document.getElementById('cholesterol-input').value;
        
        const responseArea = document.getElementById('aiResponseArea');

        // 3. KAWALAN KESELAMATAN: Jangan panggil AI kalau semua input ini kosong
        if (bpValue === '' && sugarValue === '' && cholesterolValue === '') {
            return; 
        }

        // 4. Tunjuk animasi loading (Bahasa Inggeris)
        responseArea.innerHTML = '<span class="animate-pulse text-indigo-600 font-bold">⏳ Analyzing health metrics...</span>';

        // 5. Sediakan data lengkap untuk AI
        const patientData = {
            bmi: bmiValue || 'Not specified',
            bp: bpValue || 'Not specified', 
            sugar: sugarValue || 'Not specified',
            cholesterol: cholesterolValue || 'Not specified',
            _token: '{{ csrf_token() }}'
        };

        // 6. Hantar ke API Controller
        fetch('{{ route("api.ai.suggestion") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(patientData)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                let formattedText = data.suggestion.replace(/\*/g, '').split('\n').filter(line => line.trim() !== '').map(line => `<li class="mb-1 text-indigo-900">🔹 ${line}</li>`).join('');
                responseArea.innerHTML = `<ul class="font-medium">${formattedText}</ul>`;
            } else {
                responseArea.innerHTML = `<span class="text-red-500">Error: ${data.message}</span>`;
            }
        })
        .catch(error => {
            responseArea.innerHTML = `<span class="text-red-500">Failed to connect to AI server.</span>`;
        });
    }
    </script>
</x-app-layout>