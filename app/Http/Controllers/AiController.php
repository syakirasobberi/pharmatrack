<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function generateSuggestion(Request $request)
    {
        $validated = $request->validate([
            'bmi' => 'nullable|string|max:50',
            'bp' => 'nullable|string|max:50',
            'sugar' => 'nullable|string|max:50',
            'cholesterol' => 'nullable|string|max:50',
        ]);

        try {
            $apiKey = env('GEMINI_API_KEY');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'GEMINI_API_KEY is missing from the environment configuration.',
                ], 500);
            }

            $bmi = $validated['bmi'] ?? 'Normal';
            $bp = $validated['bp'] ?? '120/80';
            $sugar = $validated['sugar'] ?? '5.0';
            $cholesterol = $validated['cholesterol'] ?? '4.0';

            $prompt = "You are an expert clinical pharmacist supporting a community pharmacy health screening workflow. A patient has the following health metrics:
                       - BMI: {$bmi}
                       - Blood Pressure: {$bp} mmHg
                       - Blood Sugar: {$sugar} mmol/L
                       - Cholesterol: {$cholesterol} mmol/L
                       
                       Based on these metrics, provide short, practical recommendations for pharmacist review.
                       You MUST strictly format your response into exactly 4 numbered points:
                       1. Food: (Give one specific dietary recommendation)
                       2. Exercise: (Give one suitable physical activity or lifestyle recommendation)
                       3. Follow-up: (Give one monitoring or referral recommendation)
                       4. Medication review: (Give one pharmacist review consideration, but do not prescribe or name a medicine unless the patient is already prescribed it)
                       
                       Keep each point one sentence. Do not use markdown symbols or bold text. Do not claim to diagnose disease.";

            $httpClient = Http::timeout(15)->acceptJson();

            if (app()->environment('local')) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->post(
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
}
