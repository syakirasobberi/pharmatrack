<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiController extends Controller
{
    private const GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    private function geminiClient(string $apiKey)
    {
        // The key goes in a header, never the URL: error messages include the URL,
        // and a key in the query string would leak into logs and the browser.
        $httpClient = Http::timeout(15)->acceptJson()->withHeaders(['x-goog-api-key' => $apiKey]);

        if (app()->environment('local')) {
            $httpClient = $httpClient->withoutVerifying();
        }

        return $httpClient;
    }

    /**
     * Send a prompt to Gemini. Returns the reply text, or a JSON error response.
     * Details are logged server-side only; the browser gets a generic message.
     */
    private function askGemini(string $prompt, string $context): string|JsonResponse
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            Log::error('Gemini API key is not configured.', ['context' => $context]);

            return response()->json([
                'success' => false,
                'message' => 'AI suggestions are not configured on this server. Please contact the administrator.',
            ], 500);
        }

        try {
            $response = $this->geminiClient($apiKey)->post(self::GEMINI_URL, [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);
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
