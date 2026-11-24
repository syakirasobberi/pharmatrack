<?php

namespace App\Http\Controllers;

use App\Models\HealthCheckup;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use App\Support\HealthSummaryPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class KioskController extends Controller
{
    /** Max Euclidean distance between face-api.js descriptors to count as the same person. */
    private const MATCH_THRESHOLD = 0.45;

    /** Minimum gap between the best and second-best patient; smaller means the match is ambiguous. */
    private const AMBIGUITY_MARGIN = 0.05;

    /** Number of frames the kiosk must send per identification attempt. */
    private const MIN_FRAMES = 3;

    private const PIN_MAX_ATTEMPTS = 5;
    private const PIN_DECAY_SECONDS = 900;

    /**
     * Match a face descriptor captured by the kiosk camera against registered patients.
     * Matching happens here so stored descriptors and patient names never leave the server.
     */
    public function identify(Request $request): JsonResponse
    {
        // Several frames captured after the kiosk's blink (liveness) check.
        $validated = $request->validate([
            'descriptors' => ['required', 'array', 'min:' . self::MIN_FRAMES, 'max:5'],
            'descriptors.*' => ['required', 'array', 'size:128'],
            'descriptors.*.*' => ['required', 'numeric', 'between:-2,2'],
        ]);

        $frames = array_map(fn ($d) => array_map('floatval', array_values($d)), $validated['descriptors']);
        $probe = self::average($frames);

        // Find the closest and second-closest registered patients to the averaged face.
        $bestId = null;
        $bestDistance = INF;
        $bestStored = null;
        $secondDistance = INF;

        Patient::query()
            ->whereNotNull('face_descriptor')
            ->where('face_descriptor', '!=', '')
            ->select(['id', 'face_descriptor'])
            ->chunkById(200, function ($patients) use ($probe, &$bestId, &$bestDistance, &$bestStored, &$secondDistance) {
                foreach ($patients as $patient) {
                    $stored = json_decode($patient->face_descriptor, true);
                    if (! is_array($stored) || count($stored) !== 128) {
                        continue;
                    }
                    $stored = array_map('floatval', $stored);
                    $distance = self::distance($stored, $probe);

                    if ($distance < $bestDistance) {
                        $secondDistance = $bestDistance;
                        $bestDistance = $distance;
                        $bestId = $patient->id;
                        $bestStored = $stored;
                    } elseif ($distance < $secondDistance) {
                        $secondDistance = $distance;
                    }
                }
            });

        if ($bestId === null || $bestDistance > self::MATCH_THRESHOLD) {
            return response()->json(['matched' => false], 404);
        }

        // Every individual frame must match too — one lucky frame is not enough.
        foreach ($frames as $frame) {
            if (self::distance($bestStored, $frame) > self::MATCH_THRESHOLD) {
                return response()->json(['matched' => false], 404);
            }
        }

        // Ambiguous: two patients are almost equally close, so refuse rather than guess.
        if ($secondDistance - $bestDistance < self::AMBIGUITY_MARGIN) {
            return response()->json(['matched' => false, 'reason' => 'ambiguous'], 404);
        }

        $patient = Patient::with('user')->findOrFail($bestId);

        $request->session()->regenerate();
        $request->session()->put([
            'kiosk_patient_id' => $patient->id,
            'kiosk_auth_at' => now()->timestamp,
        ]);

        return response()->json([
            'matched' => true,
            'name' => $patient->user?->name ?? 'Patient',
            'redirect' => route('kiosk.summary', $patient->id),
        ]);
    }

    public function summary(int $id)
    {
        $patient = Patient::with([
            'user',
            'healthCheckups' => fn ($q) => $q->latest('checkup_date'),
            'medicalHistory',
            'medications',
        ])->findOrFail($id);

        return view('kiosk.summary', compact('patient'));
    }

    public function download(int $id)
    {
        $patient = Patient::with([
            'user',
            'pharmacist',
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
            'medicalHistory',
            'medications',
        ])->findOrFail($id);

        $filename = 'kiosk-health-summary-' . $patient->id . '-' . now()->format('Y-m-d') . '.pdf';

        return response(HealthSummaryPdf::make($patient))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function updateMedical(Request $request, int $id): RedirectResponse
    {
        $patient = Patient::with(['medicalHistory', 'pharmacist'])->findOrFail($id);

        $validated = $request->validateWithBag('kioskMedical', [
            'hypertension' => ['required', 'string', 'max:255'],
            'diabetes' => ['required', 'string', 'max:255'],
            'allergies' => ['nullable', 'string', 'max:255'],
            'drug_allergies' => ['nullable', 'string', 'max:255'],
            'others' => ['nullable', 'string', 'max:255'],
            'kiosk_pin' => ['required', 'string'],
        ]);

        if ($error = $this->verifyPin($patient->pharmacist, $validated['kiosk_pin'])) {
            return back()
                ->withErrors(['kiosk_pin' => $error], 'kioskMedical')
                ->withInput($request->except('kiosk_pin'));
        }

        MedicalHistory::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'hypertension' => $validated['hypertension'],
                'diabetes' => $validated['diabetes'],
                'allergies' => $validated['allergies'] ?? null,
                'drug_allergies' => $validated['drug_allergies'] ?? null,
                'others' => $validated['others'] ?? null,
            ]
        );

        return redirect()
            ->route('kiosk.summary', $patient->id)
            ->with('kiosk_medical_success', 'Medical record updated successfully.');
    }

    public function storeCheckup(Request $request, int $id): RedirectResponse
    {
        $patient = Patient::with('pharmacist')->findOrFail($id);
        $pharmacist = $patient->pharmacist;

        $validated = $request->validateWithBag('kioskCheckup', [
            'checkup_date' => ['required', 'date'],
            'report_source' => ['nullable', 'string', 'max:255'],
            'patient_weight' => ['nullable', 'required_with:patient_height', 'numeric', 'min:1', 'max:300'],
            'patient_height' => ['nullable', 'required_with:patient_weight', 'numeric', 'min:1', 'max:250'],
            'blood_pressure' => ['nullable', 'string', 'max:255'],
            'heart_rate' => ['nullable', 'integer', 'min:30', 'max:250'],
            'haemoglobin' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'blood_sugar' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'hba1c' => ['nullable', 'numeric', 'min:3', 'max:20'],
            'albumin_globulin_ratio' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'alkaline_phosphatase' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'aspartate_transaminase' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'alanine_transaminase' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'gamma_glutamyl_transferase' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'sodium' => ['nullable', 'numeric', 'min:80', 'max:200'],
            'renal_glucose' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'cholesterol' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'ldl' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'hdl' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'kiosk_pin' => ['required', 'string'],
        ]);

        if ($error = $this->verifyPin($pharmacist, $validated['kiosk_pin'])) {
            return back()
                ->withErrors(['kiosk_pin' => $error], 'kioskCheckup')
                ->withInput($request->except('kiosk_pin'));
        }

        $checkupData = $validated;
        unset($checkupData['patient_weight'], $checkupData['patient_height'], $checkupData['kiosk_pin']);

        DB::transaction(function () use ($patient, $pharmacist, $validated, $checkupData) {
            if (isset($validated['patient_weight'], $validated['patient_height'])) {
                $heightInMeters = (float) $validated['patient_height'] / 100;

                $patient->update([
                    'weight' => $validated['patient_weight'],
                    'height' => $validated['patient_height'],
                    'bmi' => $validated['patient_weight'] / ($heightInMeters * $heightInMeters),
                ]);
            }

            HealthCheckup::create(array_merge($checkupData, [
                'patient_id' => $patient->id,
                'pharmacist_id' => $pharmacist->id,
            ]));
        });

        return redirect()
            ->route('kiosk.summary', $patient->id)
            ->with('kiosk_checkup_success', 'New check-up recorded successfully.');
    }

    /** @param array<int, float> $a @param array<int, float> $b */
    private static function distance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $value) {
            $diff = $value - $b[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /** @param array<int, array<int, float>> $frames */
    private static function average(array $frames): array
    {
        $out = array_fill(0, 128, 0.0);
        foreach ($frames as $frame) {
            foreach ($frame as $i => $value) {
                $out[$i] += $value;
            }
        }

        return array_map(fn ($v) => $v / count($frames), $out);
    }

    /**
     * Check the assigned pharmacist's kiosk PIN, locking out after repeated failures.
     * Returns an error message, or null when the PIN is valid.
     */
    private function verifyPin(?User $pharmacist, string $pin): ?string
    {
        if (! $pharmacist || $pharmacist->role !== 'pharmacist') {
            return 'This patient has no assigned pharmacist.';
        }

        if (! $pharmacist->kiosk_pin) {
            return 'The assigned pharmacist has not set a kiosk PIN yet (Profile → Kiosk PIN).';
        }

        $key = 'kiosk-pin:' . $pharmacist->id;

        if (RateLimiter::tooManyAttempts($key, self::PIN_MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return "Too many incorrect PIN attempts. Try again in {$minutes} minute(s).";
        }

        if (! Hash::check($pin, $pharmacist->kiosk_pin)) {
            RateLimiter::hit($key, self::PIN_DECAY_SECONDS);

            return 'Invalid kiosk PIN for this patient.';
        }

        RateLimiter::clear($key);

        return null;
    }
}
