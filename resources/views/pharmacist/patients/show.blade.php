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

            <div class="flex flex-col md:flex-row items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-200 gap-6">
                <div class="flex-shrink-0 relative">
                    <img
                        src="https://ui-avatars.com/api/?name={{ urlencode($patient->user->name) }}&background=eff6ff&color=1d4ed8&size=128&font-size=0.35&bold=true"
                        alt="Profile Photo"
                        class="w-24 h-24 rounded-full shadow-sm border-2 border-blue-100"
                    >
                    <div class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></div>
                </div>

                <div class="flex-1 w-full flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-extrabold text-gray-800">{{ $patient->user->name }}</h2>
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-bold">Normal</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-5 text-sm text-gray-500 mt-2 font-medium">
                            <span>{{ $patient->gender }}</span>
                            <span>{{ $patient->age }} years old</span>
                            <span>{{ $patient->user->email }}</span>
                        </div>
                        <p class="mt-2 text-sm font-bold text-blue-700">
                            Assigned Pharmacist: {{ $patient->pharmacist?->name ?? 'Unassigned' }}
                        </p>
                    </div>

                    <div class="mt-4 md:mt-0 flex flex-col items-end">
                        <div class="flex gap-2">
                            <a href="{{ route('pharmacist.patients.summary', $patient->id) }}" class="px-5 py-2 border border-blue-200 text-blue-700 font-bold text-sm rounded-full hover:bg-blue-50 transition-colors">
                                Summary
                            </a>
                            <button class="px-5 py-2 border border-gray-300 text-gray-700 font-bold text-sm rounded-full hover:bg-gray-100 transition-colors">
                                Edit
                            </button>
                            <a href="{{ route('pharmacist.checkups.create', $patient->id) }}" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-full shadow-md transition-colors">
                                + Add Check-up
                            </a>
                        </div>
                        <span class="text-xs text-gray-400 mt-2">Last Update: {{ $patient->updated_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            </div>

            @php
                $latestCheckup = $patient->healthCheckups()->first();
            @endphp

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center">
                    <span class="text-gray-500 text-sm font-bold mb-1">BMI</span>
                    <span class="text-3xl font-extrabold text-gray-800">{{ number_format($patient->bmi, 1) }}</span>
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

            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h3 class="font-extrabold text-lg text-gray-800 mb-4 flex items-center gap-2">
                    Facial Biometrics Status
                </h3>

                @if($patient->face_descriptor)
                    <div class="p-4 bg-green-50 text-green-700 border border-green-200 rounded-xl font-bold">
                        Facial biometric data is registered. Patient is ready for Quick Scan.
                    </div>
                @else
                    <div class="p-4 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-xl mb-4 font-bold text-sm">
                        No facial biometric found for this patient. Please scan now to update their profile.
                    </div>

                    <div class="flex flex-col items-center bg-gray-50 p-4 rounded-xl border border-dashed border-gray-300">
                        <p id="status" class="text-sm text-gray-500 font-bold mb-2">Loading AI models...</p>

                        <div id="video-container" class="relative inline-block overflow-hidden rounded-lg shadow-sm">
                            <video id="video" width="320" height="240" autoplay muted class="bg-black"></video>
                        </div>

                        <button type="button" id="btn-update-face" class="mt-4 px-6 py-2 bg-indigo-600 text-white font-bold rounded-full shadow hover:bg-indigo-700 transition-colors">
                            Scan & Update Face
                        </button>

                        <p id="update-success-msg" class="text-green-600 font-bold hidden mt-3">
                            Face successfully saved. Reloading profile...
                        </p>
                    </div>
                @endif
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h3 class="font-extrabold text-lg text-gray-800 mb-4 flex items-center gap-2">
                    Health Trends (Blood Sugar & Cholesterol)
                </h3>
                <div class="relative h-72 w-full">
                    <canvas id="healthChart"></canvas>
                </div>
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
                                        <p class="text-xs text-gray-500 mt-1">BP: {{ $checkup->blood_pressure }} | Sugar: {{ $checkup->blood_sugar ?? '-' }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="pl-6 text-sm text-gray-500 italic">No timeline records found.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-extrabold text-lg text-gray-800 flex items-center gap-2">Medication</h3>
                            <div class="flex gap-2">
                                <a href="{{ route('pharmacist.medication.index', $patient->id) }}" class="text-xs bg-slate-100 text-slate-700 hover:bg-slate-200 px-3 py-1.5 rounded-lg font-bold">
                                    View All
                                </a>
                                <a href="{{ route('pharmacist.medication.index', $patient->id) }}" class="text-xs bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg font-bold">
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
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-extrabold text-lg text-gray-800 flex items-center gap-2">Medical History</h3>
                            <a href="{{ route('pharmacist.patients.medical.edit', $patient->id) }}" class="text-xs bg-purple-100 text-purple-700 hover:bg-purple-200 px-3 py-1.5 rounded-lg font-bold">
                                + Update Records
                            </a>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="border border-gray-200 rounded-xl p-4">
                                <p class="text-xs text-gray-500 font-bold mb-1">Hypertension</p>
                                @if(optional($patient->medicalHistory)->hypertension == 'High Risk')
                                    <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5 rounded">High Risk</span>
                                @elseif(optional($patient->medicalHistory)->hypertension == 'Monitored')
                                    <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-0.5 rounded">Monitored</span>
                                @else
                                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded">None</span>
                                @endif
                            </div>
                            <div class="border {{ optional($patient->medicalHistory)->allergies ? 'border-red-200 bg-red-50' : 'border-gray-200' }} rounded-xl p-4">
                                <p class="text-xs {{ optional($patient->medicalHistory)->allergies ? 'text-red-500' : 'text-gray-500' }} font-bold mb-1">Allergies</p>
                                <p class="text-sm font-bold {{ optional($patient->medicalHistory)->allergies ? 'text-red-800' : 'text-gray-800' }}">
                                    {{ optional($patient->medicalHistory)->allergies ?: 'No known allergies' }}
                                </p>
                            </div>
                            <div class="border border-gray-200 rounded-xl p-4">
                                <p class="text-xs text-gray-500 font-bold mb-1">Diabetes</p>
                                <p class="text-sm font-bold text-gray-800">{{ optional($patient->medicalHistory)->diabetes ?: 'None' }}</p>
                            </div>
                            <div class="border border-gray-200 rounded-xl p-4">
                                <p class="text-xs text-gray-500 font-bold mb-1">Drug Allergies</p>
                                <p class="text-sm font-bold text-gray-800">{{ optional($patient->medicalHistory)->drug_allergies ?: 'No known allergies' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-100 to-blue-100 rounded-bl-full opacity-50 -z-10"></div>

                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-extrabold text-lg text-slate-800 flex items-center gap-2">
                                AI Clinical Suggestions
                            </h3>
                            <button id="btnAiSuggestion" onclick="getAiSuggestion()" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white text-sm font-bold rounded-xl shadow-md transition-all">
                                <span>Generate New Advice</span>
                            </button>
                        </div>

                        <div id="aiResponseArea" class="p-4 bg-slate-50 border border-slate-100 rounded-xl min-h-[100px]">
                            <p class="text-sm text-slate-500 italic text-center mt-6">Click the button above to generate personalized health advice for this patient using Gemini AI.</p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 text-right">
                            <span class="text-[10px] font-bold text-slate-400">AI-generated content. Please verify clinically.</span>
                        </div>
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
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/face-api.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('healthChart').getContext('2d');
            const chartLabels = {!! json_encode($dates) !!};
            const sugarData = {!! json_encode($sugars) !!};
            const cholesterolData = {!! json_encode($cholesterols) !!};

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Blood Sugar (mmol/L)',
                            data: sugarData,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            pointBackgroundColor: 'rgb(59, 130, 246)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Cholesterol (mmol/L)',
                            data: cholesterolData,
                            borderColor: 'rgb(245, 158, 11)',
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            pointBackgroundColor: 'rgb(245, 158, 11)',
                            tension: 0.4
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

            const video = document.getElementById('video');

            if (video) {
                const statusText = document.getElementById('status');
                const btnUpdateFace = document.getElementById('btn-update-face');
                const successMsg = document.getElementById('update-success-msg');
                const patientId = {{ $patient->id }};

                Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri('{{ asset("models") }}'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset("models") }}'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset("models") }}')
                ]).then(startVideo).catch(err => {
                    statusText.innerText = 'Error loading AI models. Please check the console.';
                    console.error('Model Load Error:', err);
                });

                function startVideo() {
                    statusText.innerText = 'Camera ready. Please look at the screen.';
                    navigator.mediaDevices.getUserMedia({ video: {} })
                        .then(stream => video.srcObject = stream)
                        .catch(() => statusText.innerText = 'Camera error. Please allow access.');
                }

                video.addEventListener('play', () => {
                    const canvas = faceapi.createCanvasFromMedia(video);
                    canvas.style.position = 'absolute';
                    canvas.style.top = '0';
                    canvas.style.left = '0';

                    document.getElementById('video-container').append(canvas);
                    const displaySize = { width: video.width, height: video.height };
                    faceapi.matchDimensions(canvas, displaySize);

                    setInterval(async () => {
                        const detections = await faceapi.detectAllFaces(video);
                        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                        if (detections) {
                            const resizedDetections = faceapi.resizeResults(detections, displaySize);
                            faceapi.draw.drawDetections(canvas, resizedDetections);
                        }
                    }, 300);
                });

                btnUpdateFace.addEventListener('click', async () => {
                    const detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();

                    if (detections) {
                        btnUpdateFace.innerText = 'Saving...';
                        btnUpdateFace.disabled = true;

                        const descriptorArray = Array.from(detections.descriptor);

                        fetch("{{ route('pharmacist.patients.updateFace') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                patient_id: patientId,
                                descriptor: descriptorArray
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                successMsg.classList.remove('hidden');
                                successMsg.classList.add('block');
                                btnUpdateFace.classList.add('hidden');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                alert('Failed to save data.');
                                btnUpdateFace.innerText = 'Scan & Update Face';
                                btnUpdateFace.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            btnUpdateFace.innerText = 'Scan & Update Face';
                            btnUpdateFace.disabled = false;
                        });
                    } else {
                        alert('Face not detected. Please look clearly at the camera.');
                    }
                });
            }
        });

        function getAiSuggestion() {
            const btn = document.getElementById('btnAiSuggestion');
            const responseArea = document.getElementById('aiResponseArea');

            btn.innerHTML = 'Thinking...';
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');

            responseArea.innerHTML = '<div class="flex justify-center py-6"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600"></div></div>';

            const patientData = {
                bmi: '{{ number_format($patient->bmi, 1) }}',
                bp: '{{ $latestCheckup ? $latestCheckup->blood_pressure : "120/80" }}',
                sugar: '{{ $latestCheckup ? $latestCheckup->blood_sugar : "5.5" }}',
                cholesterol: '{{ $latestCheckup ? $latestCheckup->cholesterol : "4.0" }}',
                _token: '{{ csrf_token() }}'
            };

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
                if (data.success) {
                    const formattedText = data.suggestion
                        .replace(/\*/g, '')
                        .split('\n')
                        .filter(line => line.trim() !== '')
                        .map(line => `<li class="mb-2 text-slate-700 text-sm">${line}</li>`)
                        .join('');

                    responseArea.innerHTML = `<ul class="p-2">${formattedText}</ul>`;
                } else {
                    responseArea.innerHTML = `<p class="text-sm text-red-500 font-bold">Error: ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                responseArea.innerHTML = '<p class="text-sm text-red-500 font-bold">Failed to connect to the server.</p>';
            })
            .finally(() => {
                btn.innerHTML = 'Generate New Advice';
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
            });
        }
    </script>
</x-app-layout>
