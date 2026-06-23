<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    private function geminiClient()
    {
        $httpClient = Http::timeout(15)->acceptJson();

        if (app()->environment('local')) {
            $httpClient = $httpClient->withoutVerifying();
        }

        return $httpClient;
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

        try {
            $apiKey = config('services.gemini.key');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'GEMINI_API_KEY is missing from the environment configuration.',
                ], 500);
            }

            $bmi = $validated['bmi'] ?? 'Normal';
            $weight = $validated['weight'] ?? 'Not specified';
            $height = $validated['height'] ?? 'Not specified';
            $bp = $validated['bp'] ?? '120/80';
            $sugar = $validated['sugar'] ?? '5.0';
            $cholesterol = $validated['cholesterol'] ?? '4.0';
            $hba1c = $validated['hba1c'] ?? 'Not specified';
            $ldl = $validated['ldl'] ?? 'Not specified';
            $hdl = $validated['hdl'] ?? 'Not specified';
            $heartRate = $validated['heart_rate'] ?? 'Not specified';
            $haemoglobin = $validated['haemoglobin'] ?? 'Not specified';
            $sodium = $validated['sodium'] ?? 'Not specified';
            $ggt = $validated['ggt'] ?? 'Not specified';

            $prompt = "You are an expert clinical pharmacist supporting a community pharmacy health screening workflow. A patient has the following health metrics:
                       - BMI: {$bmi}
                       - Weight from patient profile: {$weight} kg
                       - Height from patient profile: {$height} cm
                       - Blood Pressure: {$bp} mmHg
                       - Heart Rate: {$heartRate} bpm
                       - Haemoglobin: {$haemoglobin} g/dL
                       - Blood Sugar: {$sugar} mmol/L
                       - HbA1c: {$hba1c}
                       - Cholesterol: {$cholesterol} mmol/L
                       - LDL: {$ldl}
                       - HDL: {$hdl}
                       - Sodium: {$sodium}
                       - Gamma glutamyl transferase: {$ggt}
                       
                       Based on these metrics, provide short, practical recommendations for pharmacist review.
                       You MUST strictly format your response into exactly 4 numbered points:
                       1. Food: (Give one specific dietary recommendation)
                       2. Exercise: (Give one suitable physical activity or lifestyle recommendation)
                       3. Follow-up: (Give one monitoring or referral recommendation)
                       4. Medication review: (Give one pharmacist review consideration, but do not prescribe or name a medicine unless the patient is already prescribed it)
                       
                       Keep each point one sentence. Do not use markdown symbols or bold text. Do not claim to diagnose disease.";

            $response = $this->geminiClient()->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                ['contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]]
            );

            if ($response->successful()) {
                $aiText = $response->json('candidates.0.content.parts.0.text');

                if (!$aiText) {
                    return response()->json([
                        'success' => false,
                        'message' => 'AI response was empty.',
                    ], 502);
                }

                return response()->json(['success' => true, 'suggestion' => $aiText]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gemini API error: ' . $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'System error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generatePatientSummary(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->with([
            'user',
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
        ])->findOrFail($id);

        try {
            $apiKey = config('services.gemini.key');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'GEMINI_API_KEY is missing from the environment configuration.',
                ], 500);
            }

            $latest = $patient->healthCheckups->first();

            if (! $latest) {
                return response()->json([
                    'success' => false,
                    'message' => 'No check-up record is available for AI suggestion.',
                ], 422);
            }

            $bmi = $patient->bmi ?? 'Normal';
            $weight = $patient->weight ?? 'Not specified';
            $height = $patient->height ?? 'Not specified';
            $bp = $latest->blood_pressure ?? 'Not specified';
            $sugar = $latest->blood_sugar ?? 'Not specified';
            $cholesterol = $latest->cholesterol ?? 'Not specified';
            $hba1c = $latest->hba1c ?? 'Not specified';
            $ldl = $latest->ldl ?? 'Not specified';
            $hdl = $latest->hdl ?? 'Not specified';
            $heartRate = $latest->heart_rate ?? 'Not specified';
            $haemoglobin = $latest->haemoglobin ?? 'Not specified';
            $sodium = $latest->sodium ?? 'Not specified';
            $ggt = $latest->gamma_glutamyl_transferase ?? 'Not specified';

            $prompt = "You are an expert clinical pharmacist supporting a community pharmacy health screening workflow. A patient has the following health metrics:
                       - BMI: {$bmi}
                       - Weight from patient profile: {$weight} kg
                       - Height from patient profile: {$height} cm
                       - Blood Pressure: {$bp} mmHg
                       - Heart Rate: {$heartRate} bpm
                       - Haemoglobin: {$haemoglobin} g/dL
                       - Blood Sugar: {$sugar} mmol/L
                       - HbA1c: {$hba1c}
                       - Cholesterol: {$cholesterol} mmol/L
                       - LDL: {$ldl}
                       - HDL: {$hdl}
                       - Sodium: {$sodium}
                       - Gamma glutamyl transferase: {$ggt}
                       
                       Based on these metrics, provide short, practical recommendations for pharmacist review.
                       You MUST strictly format your response into exactly 4 numbered points:
                       1. Food: (Give one specific dietary recommendation)
                       2. Exercise: (Give one suitable physical activity or lifestyle recommendation)
                       3. Follow-up: (Give one monitoring or referral recommendation)
                       4. Medication review: (Give one pharmacist review consideration, but do not prescribe or name a medicine unless the patient is already prescribed it)
                       
                       Keep each point one sentence. Do not use markdown symbols or bold text. Do not claim to diagnose disease.";

            $response = $this->geminiClient()->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                ['contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]]
            );

            if ($response->successful()) {
                $suggestion = $response->json('candidates.0.content.parts.0.text');

                if (!$suggestion) {
                    return response()->json([
                        'success' => false,
                        'message' => 'AI response was empty.',
                    ], 502);
                }

                $latest->update(['ai_suggestion' => $suggestion]);

                return response()->json(['success' => true, 'suggestion' => $suggestion]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gemini API error: ' . $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'System error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
