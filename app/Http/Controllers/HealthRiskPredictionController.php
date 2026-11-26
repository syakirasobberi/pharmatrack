<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Support\RiskModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthRiskPredictionController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $patient = Patient::assignedTo(request()->user())->with([
            'healthCheckups' => fn ($query) => $query->latest('checkup_date')->latest('id'),
        ])->findOrFail($id);

        return response()->json($this->predictForPatient($patient));
    }

    public function predictForPatient(Patient $patient): array
    {
        $latestCheckup = $patient->healthCheckups->first();

        if (!$latestCheckup) {
            return $this->emptyPrediction('No health check-up data is available for ML prediction yet.');
        }

        $bmi = (float) $patient->bmi;
        $bloodSugar = (float) $latestCheckup->blood_sugar;
        $bloodPressure = $this->parseSystolicPressure($latestCheckup->blood_pressure);
        $cholesterol = (float) $latestCheckup->cholesterol;

        if (!$bmi || !$bloodSugar || !$bloodPressure || !$cholesterol) {
            return $this->emptyPrediction('BMI, blood pressure, blood sugar, and cholesterol are required.');
        }

        // Decision tree trained from health_data.csv, in PHP (same results as the old predict.py,
        // which needed Python + shell_exec — both unavailable on shared hosting).
        try {
            $prediction = RiskModel::predict($bmi, $bloodSugar, $bloodPressure, $cholesterol);
        } catch (Throwable $exception) {
            Log::error('Risk prediction failed.', ['patient_id' => $patient->id, 'exception' => $exception]);

            return $this->emptyPrediction('Prediction could not be generated. Please check that health_data.csv is present and valid.');
        }

        $risk = $prediction['risk'];
        $confidence = $prediction['confidence'];
        $riskFactors = $this->riskFactors($bmi, $bloodSugar, $bloodPressure, $cholesterol);

        return [
            'success' => true,
            'risk' => $risk,
            'risk_label' => $risk . ' Risk',
            'confidence' => $confidence,
            'risk_score' => (int) str_replace('%', '', $confidence),
            'factors' => $riskFactors,
            'summary' => $this->summaryText($risk, $riskFactors),
            'inputs' => [
                'bmi' => round($bmi, 1),
                'blood_sugar' => round($bloodSugar, 1),
                'blood_pressure' => round($bloodPressure),
                'cholesterol' => round($cholesterol, 1),
                'lifestyle_score' => $this->lifestyleScore($risk),
            ],
        ];
    }

    private function emptyPrediction(string $message): array
    {
        return [
            'success' => false,
            'risk' => 'Not Available',
            'risk_label' => 'Not Available',
            'confidence' => '0%',
            'risk_score' => 0,
            'factors' => [],
            'summary' => $message,
            'inputs' => [
                'bmi' => 0,
                'blood_sugar' => 0,
                'blood_pressure' => 0,
                'cholesterol' => 0,
                'lifestyle_score' => 0,
            ],
        ];
    }

    private function parseSystolicPressure(?string $bloodPressure): float
    {
        if (!$bloodPressure) {
            return 0;
        }

        return (float) preg_replace('/[^0-9.].*/', '', $bloodPressure);
    }



    private function riskFactors(float $bmi, float $bloodSugar, float $bloodPressure, float $cholesterol): array
    {
        $factors = [];

        if ($bloodSugar >= 7.0) {
            $factors[] = 'High Blood Sugar';
        } elseif ($bloodSugar > 6.0) {
            $factors[] = 'Borderline Blood Sugar';
        }

        if ($bmi >= 30) {
            $factors[] = 'Obese BMI';
        } elseif ($bmi >= 25) {
            $factors[] = 'Elevated BMI';
        }

        if ($bloodPressure >= 140) {
            $factors[] = 'High Blood Pressure';
        } elseif ($bloodPressure >= 130) {
            $factors[] = 'Borderline Blood Pressure';
        }

        if ($cholesterol >= 6.2) {
            $factors[] = 'High Cholesterol';
        } elseif ($cholesterol >= 5.2) {
            $factors[] = 'Borderline Cholesterol';
        }

        return $factors ?: ['No major risk factors detected'];
    }

    private function summaryText(string $risk, array $riskFactors): string
    {
        $factorText = implode(', ', array_slice($riskFactors, 0, 3));

        if ($risk === 'High') {
            return "The patient shows high health risk due to {$factorText}. Prompt pharmacist review, lifestyle counselling, and medical follow-up are recommended.";
        }

        if ($risk === 'Moderate') {
            return "The patient shows moderate health risk due to {$factorText}. Regular exercise, dietary monitoring, and follow-up check-ups are recommended.";
        }

        return "The patient shows low health risk based on the current readings. Continue healthy lifestyle habits and routine monitoring.";
    }

    private function lifestyleScore(string $risk): int
    {
        return match ($risk) {
            'High' => 35,
            'Moderate' => 62,
            default => 82,
        };
    }
}
