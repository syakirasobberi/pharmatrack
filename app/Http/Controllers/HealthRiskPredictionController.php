<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class HealthRiskPredictionController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $patient = Patient::assignedTo(request()->user())->with([
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
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

        $scriptPath = base_path('predict.py');
        $pythonPath = $this->pythonExecutable();
        $command = escapeshellarg($pythonPath) . ' '
            . escapeshellarg($scriptPath) . ' '
            . escapeshellarg((string) $bmi) . ' '
            . escapeshellarg((string) $bloodSugar) . ' '
            . escapeshellarg((string) $bloodPressure) . ' '
            . escapeshellarg((string) $cholesterol)
            . ' 2>&1';

        $output = shell_exec($command);
        $prediction = json_decode(trim((string) $output), true);

        if (!is_array($prediction) || isset($prediction['error'])) {
            return $this->emptyPrediction('Prediction could not be generated. Please check Python, pandas, and scikit-learn installation.');
        }

        $risk = $prediction['risk'] ?? 'Moderate';
        $confidence = $prediction['confidence'] ?? '0%';
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

    private function pythonExecutable(): string
    {
        $configuredPath = env('PYTHON_PATH');

        if ($configuredPath && file_exists($configuredPath)) {
            return $configuredPath;
        }

        $laragonPython = 'C:\\laragon\\bin\\python\\python-3.10\\python.exe';

        if (PHP_OS_FAMILY === 'Windows' && file_exists($laragonPython)) {
            return $laragonPython;
        }

        return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
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
