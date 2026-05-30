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

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-8">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Step 1</p>
                            <p class="mt-1 font-extrabold text-gray-900">Upload full report</p>
                            <p class="mt-1 text-xs text-gray-600">Use image or scanned PDF checkup result.</p>
                        </div>
                        <div class="rounded-2xl border border-cyan-100 bg-cyan-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-cyan-700">Step 2</p>
                            <p class="mt-1 font-extrabold text-gray-900">Extract readings</p>
                            <p class="mt-1 text-xs text-gray-600">OCR reads BP, glucose, and cholesterol.</p>
                        </div>
                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Step 3</p>
                            <p class="mt-1 font-extrabold text-gray-900">Review values</p>
                            <p class="mt-1 text-xs text-gray-600">Pharmacist confirms before saving.</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Step 4</p>
                            <p class="mt-1 font-extrabold text-gray-900">Generate advice</p>
                            <p class="mt-1 text-xs text-gray-600">Food, exercise, follow-up, and review notes.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="md:col-span-2 border-b border-gray-100 pb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Check-up Date <span class="text-red-500">*</span></label>
                            <input type="date" name="checkup_date" value="{{ date('Y-m-d') }}" required 
                                class="w-full md:w-1/2 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                        </div>

                        <div class="md:col-span-2 bg-cyan-50 border border-cyan-100 rounded-2xl p-5">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div>
                                    <h3 class="font-extrabold text-cyan-900 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-cyan-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 2h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 5H19a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        OCR Autofill From Checkup Report
                                    </h3>
                                    <p class="text-sm text-cyan-800 mt-1">Upload a full checkup report image or PDF. The system will extract values and autofill the fields below.</p>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3">
                                    <input type="file" id="ocr-image" accept="image/*,.pdf,application/pdf" class="block w-full text-sm text-cyan-900 file:mr-4 file:rounded-xl file:border-0 file:bg-white file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-cyan-800 hover:file:bg-cyan-100">
                                    <button type="button" id="extract-ocr-btn" class="rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-cyan-700">
                                        Extract Readings
                                    </button>
                                </div>
                            </div>

                            <div id="ocr-status" class="mt-4 text-sm font-semibold text-cyan-800">
                                Choose a report image or PDF, then click Extract Readings. Please review values before saving.
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
                                <div class="rounded-xl bg-white border border-cyan-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-cyan-700">Detected BP</p>
                                    <p id="detected-bp" class="mt-1 text-lg font-extrabold text-slate-900">-</p>
                                </div>
                                <div class="rounded-xl bg-white border border-cyan-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-cyan-700">Detected Sugar</p>
                                    <p id="detected-sugar" class="mt-1 text-lg font-extrabold text-slate-900">-</p>
                                </div>
                                <div class="rounded-xl bg-white border border-cyan-100 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-cyan-700">Detected Cholesterol</p>
                                    <p id="detected-cholesterol" class="mt-1 text-lg font-extrabold text-slate-900">-</p>
                                </div>
                            </div>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs font-bold text-cyan-700">Show OCR text</summary>
                                <pre id="ocr-output" class="mt-2 max-h-40 overflow-auto rounded-xl bg-white p-3 text-xs text-slate-600 border border-cyan-100 whitespace-pre-wrap"></pre>
                            </details>
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

                    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5 mb-6">
                        <h3 class="font-bold text-indigo-800 flex items-center gap-2 mb-2">
                            AI Recommendation Preview
                        </h3>
                        <div id="aiResponseArea" class="text-sm text-indigo-600 italic">
                            Upload a report or enter readings manually. The system will generate food, exercise, follow-up, and pharmacist review suggestions.
                        </div>
                        <p class="mt-3 text-xs font-semibold text-indigo-700">
                            Medication notes are for pharmacist review only. The system does not automatically prescribe medication.
                        </p>
                    </div>

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
    const ocrImageInput = document.getElementById('ocr-image');
    const extractOcrButton = document.getElementById('extract-ocr-btn');
    const ocrStatus = document.getElementById('ocr-status');
    const ocrOutput = document.getElementById('ocr-output');

    function normalizeOcrText(text) {
        return text
            .replace(/[|]/g, '1')
            .replace(/[，]/g, '.')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function findBloodPressure(text) {
        const directMatch = text.match(/\b([8-9]\d|1\d{2}|2[0-4]\d)\s*[/\\]\s*([4-9]\d|1[0-4]\d)\b/);
        if (directMatch) {
            return `${directMatch[1]}/${directMatch[2]}`;
        }

        const systolicMatch = text.match(/systolic[^0-9]{0,24}([8-9]\d|1\d{2}|2[0-4]\d)/i);
        const diastolicMatch = text.match(/diastolic[^0-9]{0,24}([4-9]\d|1[0-4]\d)/i);

        if (systolicMatch && diastolicMatch) {
            return `${systolicMatch[1]}/${diastolicMatch[1]}`;
        }

        return null;
    }

    function findNumberNearLabels(text, labels, min, max) {
        for (const label of labels) {
            const pattern = new RegExp(`${label}[^0-9]{0,24}(\\d{1,2}(?:\\.\\d{1,2})?)`, 'i');
            const match = text.match(pattern);

            if (match) {
                const value = Number.parseFloat(match[1]);

                if (value >= min && value <= max) {
                    return value.toFixed(2);
                }
            }
        }

        return null;
    }

    function findLooseReading(text, min, max, ignoredValues = []) {
        const matches = [...text.matchAll(/\b\d{1,2}(?:\.\d{1,2})?\b/g)]
            .map(match => Number.parseFloat(match[0]))
            .filter(value => value >= min && value <= max)
            .filter(value => !ignoredValues.some(ignored => Math.abs(ignored - value) < 0.01));

        return matches.length ? matches[0].toFixed(2) : null;
    }

    function autofillFromOcr(rawText) {
        const text = normalizeOcrText(rawText);
        const bp = findBloodPressure(text);
        const sugar = findNumberNearLabels(text, [
            'blood sugar',
            'blood glucose',
            'fasting glucose',
            'fasting blood glucose',
            'random glucose',
            'random blood glucose',
            'glucose',
            'sugar',
            'glu',
            'bs'
        ], 2, 30);
        const cholesterol = findNumberNearLabels(text, [
            'total cholesterol',
            'cholesterol total',
            'serum cholesterol',
            'cholesterol',
            'chol'
        ], 2, 20);

        if (bp) {
            document.getElementById('bp-input').value = bp;
            document.getElementById('detected-bp').textContent = bp;
        } else {
            document.getElementById('detected-bp').textContent = '-';
        }

        if (sugar) {
            document.getElementById('sugar-input').value = sugar;
            document.getElementById('detected-sugar').textContent = sugar;
        } else {
            document.getElementById('detected-sugar').textContent = '-';
        }

        if (cholesterol) {
            document.getElementById('cholesterol-input').value = cholesterol;
            document.getElementById('detected-cholesterol').textContent = cholesterol;
        } else {
            document.getElementById('detected-cholesterol').textContent = '-';
        }

        const found = [
            bp ? `BP ${bp}` : null,
            sugar ? `Sugar ${sugar}` : null,
            cholesterol ? `Cholesterol ${cholesterol}` : null,
        ].filter(Boolean);

        if (found.length) {
            ocrStatus.innerHTML = `<span class="text-green-700">Autofilled: ${found.join(', ')}. Please review before saving.</span>`;
            generateAiAuto();
        } else {
            ocrStatus.innerHTML = '<span class="text-amber-700">OCR completed, but no clear readings were found. Please enter values manually.</span>';
        }
    }

    async function renderPdfPages(file, maxPages = 6) {
        if (!window.pdfjsLib) {
            throw new Error('PDF library is not ready yet.');
        }

        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const data = await file.arrayBuffer();
        const pdf = await window.pdfjsLib.getDocument({ data }).promise;
        const pageCount = Math.min(pdf.numPages, maxPages);
        const canvases = [];

        for (let pageNumber = 1; pageNumber <= pageCount; pageNumber++) {
            ocrStatus.textContent = `Preparing PDF page ${pageNumber} of ${pageCount}...`;
            const page = await pdf.getPage(pageNumber);
            const viewport = page.getViewport({ scale: 2 });
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await page.render({ canvasContext: context, viewport }).promise;
            canvases.push({ canvas, pageNumber, pageCount });
        }

        return canvases;
    }

    async function recognizeSource(source, label) {
        const result = await Tesseract.recognize(source, 'eng', {
            logger: progress => {
                if (progress.status === 'recognizing text') {
                    ocrStatus.textContent = `${label}: recognizing text... ${Math.round(progress.progress * 100)}%`;
                }
            }
        });

        return result.data.text || '';
    }

    if (extractOcrButton) {
        extractOcrButton.addEventListener('click', async () => {
            const file = ocrImageInput.files[0];

            if (!file) {
                ocrStatus.innerHTML = '<span class="text-red-600">Please choose an image first.</span>';
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
            } catch (error) {
                console.error(error);
                ocrStatus.innerHTML = '<span class="text-red-600">OCR failed. Please try a clearer image or enter values manually.</span>';
            } finally {
                extractOcrButton.disabled = false;
                extractOcrButton.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        });
    }

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
        responseArea.innerHTML = '<span class="animate-pulse text-indigo-600 font-bold">Analyzing health metrics...</span>';

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
                const categoryStyles = {
                    food: 'border-emerald-100 bg-emerald-50 text-emerald-900',
                    exercise: 'border-blue-100 bg-blue-50 text-blue-900',
                    'follow-up': 'border-amber-100 bg-amber-50 text-amber-900',
                    'medication review': 'border-purple-100 bg-purple-50 text-purple-900',
                };

                const cards = data.suggestion
                    .replace(/\*/g, '')
                    .split('\n')
                    .map(line => line.trim())
                    .filter(line => line !== '')
                    .map(line => {
                        const cleaned = line.replace(/^\d+\.\s*/, '');
                        const [rawTitle, ...rest] = cleaned.split(':');
                        const title = rawTitle.trim();
                        const body = rest.join(':').trim() || cleaned;
                        const style = categoryStyles[title.toLowerCase()] || 'border-indigo-100 bg-white text-indigo-900';

                        return `
                            <div class="rounded-xl border ${style} p-4">
                                <p class="text-xs font-extrabold uppercase tracking-wide">${title}</p>
                                <p class="mt-1 text-sm font-medium leading-6">${body}</p>
                            </div>
                        `;
                    })
                    .join('');

                responseArea.innerHTML = `<div class="grid grid-cols-1 md:grid-cols-2 gap-3">${cards}</div>`;
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
