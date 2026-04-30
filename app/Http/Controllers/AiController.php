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

            $prompt = "You are an expert clinical pharmacist. A patient has the following health metrics: 
                       - BMI: {$bmi}
                       - Blood Pressure: {$bp} mmHg
                       - Blood Sugar: {$sugar} mmol/L
                       - Cholesterol: {$cholesterol} mmol/L
                       
                       Based on these metrics, provide a short, professional health suggestion. 
                       You MUST strictly format your response into exactly 3 bullet points based on these specific categories:
                       1. Food: (Give one specific food/dietary advice)
                       2. Drinks: (Give one specific beverage advice)
                       3. Sports: (Give one specific exercise/lifestyle advice)
                       
                       Do not use bold text or markdown, just simple sentences.";

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
