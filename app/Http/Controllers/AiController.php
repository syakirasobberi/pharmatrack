<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Support\ReportReadings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiController extends Controller
{
    private const GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    private function geminiClient(string $apiKey, int $timeout = 15)
    {
        // The key goes in a header, never the URL: error messages include the URL,
        // and a key in the query string would leak into logs and the browser.
        $httpClient = Http::timeout($timeout)->acceptJson()->withHeaders(['x-goog-api-key' => $apiKey]);

        if (app()->environment('local')) {
            $httpClient = $httpClient->withoutVerifying();
        }

        return $httpClient;
    }

    /**
     * Send a request body to Gemini. Returns the reply text, or a JSON error response.
     * Details are logged server-side only; the browser gets a generic message.
     */
    private function callGemini(array $body, string $context, int $timeout = 15): string|JsonResponse
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            Log::error('Gemini API key is not configured.', ['context' => $context]);

            return response()->json([
                'success' => false,
                'message' => 'AI features are not configured on this server. Please contact the administrator.',
            ], 500);
        }

        try {
            $response = $this->geminiClient($apiKey, $timeout)->post(self::GEMINI_URL, $body);
        } catch (Throwable $exception) {
            Log::error('Gemini request failed.', ['context' => $context, 'error' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not reach the AI service. Please try again in a moment.',
            ], 502);
        }

        if (! $response->successful()) {
            Log::error('Gemini returned an error.', [
                'context' => $context,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The AI service returned an error. Please try again later.',
            ], 502);
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! $text) {
            return response()->json([
                'success' => false,
                'message' => 'AI response was empty.',
            ], 502);
        }

        return $text;
    }

    private function askGemini(string $prompt, string $context): string|JsonResponse
    {
        return $this->callGemini(['contents' => [['parts' => [['text' => $prompt]]]]], $context);
    }

    /**
     * Read the readings off an uploaded check-up / lab report image or PDF with Gemini.
     * The file is sent to Gemini and not stored. Values are unit-converted and
     * range-checked before they reach the form, and the pharmacist reviews them.
     */
    public function extractReport(Request $request): JsonResponse
    {
        if (! config('services.gemini.report_uploads')) {
            return response()->json([
                'success' => false,
                'message' => 'Smart Extract is turned off. Use Extract Readings, which reads the report on this computer.',
            ], 403);
        }

        $request->validate([
            'report' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ], [
            'report.uploaded' => 'The report could not be uploaded. Try a smaller file (under 10 MB).',
            'report.mimes' => 'Please upload a JPG, PNG, WEBP image or a PDF.',
            'report.max' => 'The report must be smaller than 10 MB.',
        ]);

        $file = $request->file('report');
        $mime = $file->getMimeType() === 'application/pdf' ? 'application/pdf' : ($file->getMimeType() ?: 'image/jpeg');

        $prompt = <<<'PROMPT'
            You are reading a health screening or laboratory report for a community pharmacy.
            Extract only the patient's measured RESULT for each field in the schema.
            Rules:
            - Use the result column, never the reference range, flag or previous result.
            - Copy the number exactly as printed and give the unit exactly as printed (for example mmol/L, mg/dL, g/dL, g/L, %, mmol/mol, U/L).
            - blood_sugar is blood/serum/plasma glucose (fasting or random). renal_glucose is urine glucose only.
            - cholesterol is TOTAL cholesterol, not HDL, LDL, non-HDL or a ratio.
            - If a field is not on the report or is unreadable, return null for it. Do not guess or calculate values.
            - Do not return names, identity numbers, addresses or any other personal details.
            PROMPT;

        @set_time_limit(90);

        $text = $this->callGemini([
            'contents' => [[
                'parts' => [
                    ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode($file->get())]],
                    ['text' => $prompt],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0,
                'responseMimeType' => 'application/json',
                'responseSchema' => ReportReadings::responseSchema(),
                'thinkingConfig' => ['thinkingBudget' => 0],
            ],
        ], 'report extraction', 60);

        if ($text instanceof JsonResponse) {
            return $text;
        }

        $raw = json_decode($text, true);

        if (! is_array($raw)) {
            Log::warning('Gemini report extraction returned non-JSON.', ['length' => strlen($text)]);

            return response()->json(['success' => false, 'message' => 'The AI could not read this report. Please try a clearer image.'], 502);
        }

        $result = ReportReadings::normalize($raw);

        return response()->json(['success' => true] + $result);
    }

    /**
     * The one prompt used by both the check-up form and the patient summary.
     * Missing readings become "Not recorded" so the AI never assumes they are normal.
     *
     * @param  array<string, mixed>  $m  keys: bmi, weight, height, bp, heart_rate, haemoglobin,
     *                                   sugar, hba1c, cholesterol, ldl, hdl, sodium, ggt
     */
    private function buildPrompt(array $m): string
    {
        $v = fn (string $key) => (isset($m[$key]) && trim((string) $m[$key]) !== '' && $m[$key] !== 'Not specified')
            ? trim((string) $m[$key])
            : 'Not recorded';

        return <<<PROMPT
            You are an expert clinical pharmacist supporting a community pharmacy health screening workflow. A patient has the following health metrics:
            - BMI: {$v('bmi')}
            - Weight from patient profile: {$v('weight')} kg
            - Height from patient profile: {$v('height')} cm
            - Blood Pressure: {$v('bp')} mmHg
            - Heart Rate: {$v('heart_rate')} bpm
            - Haemoglobin: {$v('haemoglobin')} g/dL
            - Blood Sugar: {$v('sugar')} mmol/L
            - HbA1c: {$v('hba1c')}
            - Cholesterol: {$v('cholesterol')} mmol/L
            - LDL: {$v('ldl')}
            - HDL: {$v('hdl')}
            - Sodium: {$v('sodium')}
            - Gamma glutamyl transferase: {$v('ggt')}

            Base your advice only on the recorded values; ignore any metric marked 'Not recorded' and do not assume it is normal. Provide short, practical recommendations for pharmacist review.
            You MUST strictly format your response into exactly 4 numbered points:
            1. Food: (Give one specific dietary recommendation)
            2. Exercise: (Give one suitable physical activity or lifestyle recommendation)
            3. Follow-up: (Give one monitoring or referral recommendation)
            4. Medication review: (Give one pharmacist review consideration, but do not prescribe or name a medicine unless the patient is already prescribed it)

            Keep each point one sentence. Do not use markdown symbols or bold text. Do not claim to diagnose disease.
            PROMPT;
    }

    public function generateSuggestion(Request $request)
    {
        $validated = $request->validate([
            'bmi' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'height' => 'nullable|string|max:50',
            'bp' => 'nullable|string|max:50',
            'sugar' => 'nullable|string|max:50',
            'cholesterol' => 'nullable|string|max:50',
            'hba1c' => 'nullable|string|max:50',
            'ldl' => 'nullable|string|max:50',
            'hdl' => 'nullable|string|max:50',
            'heart_rate' => 'nullable|string|max:50',
            'haemoglobin' => 'nullable|string|max:50',
            'sodium' => 'nullable|string|max:50',
            'ggt' => 'nullable|string|max:50',
        ]);

        $prompt = $this->buildPrompt([
            'bmi' => $validated['bmi'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'height' => $validated['height'] ?? null,
            'bp' => $validated['bp'] ?? null,
            'heart_rate' => $validated['heart_rate'] ?? null,
            'haemoglobin' => $validated['haemoglobin'] ?? null,
            'sugar' => $validated['sugar'] ?? null,
            "hba1c" => $validated["hba1c"] ?? null,
            'cholesterol' => $validated['cholesterol'] ?? null,
            'ldl' => $validated['ldl'] ?? null,
            'hdl' => $validated['hdl'] ?? null,
            'sodium' => $validated['sodium'] ?? null,
            'ggt' => $validated['ggt'] ?? null,
        ]);

        $aiText = $this->askGemini($prompt, 'checkup-form suggestion');

        if ($aiText instanceof JsonResponse) {
            return $aiText;
        }

        return response()->json(['success' => true, 'suggestion' => $aiText]);
    }

    public function generatePatientSummary(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->with([
            'user',
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
        ])->findOrFail($id);

        $latest = $patient->healthCheckups->first();

        if (! $latest) {
            return response()->json([
                'success' => false,
                'message' => 'No check-up record is available for AI suggestion.',
            ], 422);
        }

        $prompt = $this->buildPrompt([
            'bmi' => $patient->bmi,
            'weight' => $patient->weight,
            'height' => $patient->height,
            'bp' => $latest->blood_pressure,
            'heart_rate' => $latest->heart_rate,
            'haemoglobin' => $latest->haemoglobin,
            'sugar' => $latest->blood_sugar,
            "hba1c" => $latest->hba1c,
            'cholesterol' => $latest->cholesterol,
            'ldl' => $latest->ldl,
            'hdl' => $latest->hdl,
            'sodium' => $latest->sodium,
            'ggt' => $latest->gamma_glutamyl_transferase,
        ]);

        $suggestion = $this->askGemini($prompt, "patient {$patient->id} summary");

        if ($suggestion instanceof JsonResponse) {
            return $suggestion;
        }

        $latest->update(['ai_suggestion' => $suggestion]);

        return response()->json(['success' => true, 'suggestion' => $suggestion]);
    }
}
