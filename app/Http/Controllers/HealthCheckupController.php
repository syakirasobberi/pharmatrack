<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\HealthCheckup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckupController extends Controller
{
    /** Readings shown on the record/edit forms, with their labels and units (used by the edit view). */
    public const METRICS = [
        'blood_pressure'             => ['Blood Pressure', 'mmHg', 'e.g. 120/80'],
        'heart_rate'                 => ['Heart Rate', 'bpm', 'e.g. 75'],
        'haemoglobin'                => ['Haemoglobin', 'g/dL', 'e.g. 13.5'],
        'blood_sugar'                => ['Blood Sugar', 'mmol/L', 'e.g. 5.5'],
        'hba1c'                      => ['HbA1c', '%', 'e.g. 5.4'],
        'albumin_globulin_ratio'     => ['A/G Ratio', '', 'e.g. 1.4'],
        'alkaline_phosphatase'       => ['Alkaline Phosphatase (ALP)', 'U/L', 'e.g. 85'],
        'aspartate_transaminase'     => ['AST', 'U/L', 'e.g. 25'],
        'alanine_transaminase'       => ['ALT', 'U/L', 'e.g. 30'],
        'gamma_glutamyl_transferase' => ['GGT', 'U/L', 'e.g. 35'],
        'sodium'                     => ['Sodium', 'mmol/L', 'e.g. 140'],
        'renal_glucose'              => ['Renal Glucose', 'mmol/L', 'e.g. 0'],
        'cholesterol'                => ['Total Cholesterol', 'mmol/L', 'e.g. 4.8'],
        'ldl'                        => ['LDL', 'mmol/L', 'e.g. 2.6'],
        'hdl'                        => ['HDL', 'mmol/L', 'e.g. 1.3'],
    ];

    public const REPORT_SOURCES = [
        'klinik_kesihatan' => 'Klinik Kesihatan (KKM)',
        'private_clinic'   => 'Private Clinic',
        'hospital'         => 'Hospital',
        'private_lab'      => 'Private Lab',
        'home_device'      => 'Home Device / Self-measured',
    ];

    /** Validation shared by "record" and "edit", so the limits can never drift apart. */
    private static function readingRules(): array
    {
        return [
            'checkup_date'   => 'required|date|before_or_equal:today',
            'blood_pressure' => ['nullable', 'string', 'max:20', 'regex:/^\d{2,3}\s*\/\s*\d{2,3}$/'],
            'heart_rate'     => 'nullable|integer|min:30|max:250',
            'haemoglobin'    => 'nullable|numeric|min:0|max:30',
            'blood_sugar'    => 'nullable|numeric|min:0|max:50',
            'hba1c'          => 'nullable|numeric|min:3|max:20',
            'albumin_globulin_ratio'      => 'nullable|numeric|min:0|max:10',
            'alkaline_phosphatase'        => 'nullable|numeric|min:0|max:2000',
            'aspartate_transaminase'      => 'nullable|numeric|min:0|max:2000',
            'alanine_transaminase'        => 'nullable|numeric|min:0|max:2000',
            'gamma_glutamyl_transferase'  => 'nullable|numeric|min:0|max:2000',
            'sodium'         => 'nullable|numeric|min:80|max:200',
            'renal_glucose'  => 'nullable|numeric|min:0|max:50',
            'cholesterol'    => 'nullable|numeric|min:0|max:30',
            'ldl'            => 'nullable|numeric|min:0|max:20',
            'hdl'            => 'nullable|numeric|min:0|max:10',
            'report_source'  => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:2000',
        ];
    }

    private const READING_MESSAGES = [
        'blood_pressure.regex' => 'Blood pressure must look like 120/80.',
        'checkup_date.before_or_equal' => 'The check-up date cannot be in the future.',
    ];

    // Paparkan borang Health Check-up untuk pesakit yang dipilih
    public function create($id)
    {
        $patient = Patient::assignedTo(Auth::user())->with('user')->findOrFail($id);
        return view('pharmacist.checkups.create', compact('patient'));
    }

    // Simpan data Health Check-up ke dalam database
    public function store(Request $request, $id)
    {
        $validated = $request->validate(self::readingRules() + [
            'patient_weight' => 'nullable|required_with:patient_height|numeric|min:1|max:300',
            'patient_height' => 'nullable|required_with:patient_weight|numeric|min:1|max:250',
            'ai_suggestion'  => 'nullable|string|max:5000',
        ], array_map('__', self::READING_MESSAGES));

        $patient = Patient::assignedTo($request->user())->findOrFail($id);

        try {
            $checkupData = $validated;
            unset($checkupData['patient_weight'], $checkupData['patient_height']);

            DB::transaction(function () use ($patient, $validated, $checkupData) {
                if (isset($validated['patient_weight'], $validated['patient_height'])) {
                    $heightInMeters = (float) $validated['patient_height'] / 100;

                    $patient->update([
                        'weight' => $validated['patient_weight'],
                        'height' => $validated['patient_height'],
                        'bmi' => $validated['patient_weight'] / ($heightInMeters * $heightInMeters),
                    ]);
                }

                HealthCheckup::create(array_merge($checkupData, [
                    'patient_id'    => $patient->id,
                    'pharmacist_id' => Auth::id(),
                ]));
            });

            return redirect()
                ->route('pharmacist.patients.show', $patient->id)
                ->with('success', __('Health checkup record saved successfully!'));

        } catch (\Throwable $e) {
            // Details (SQL, table names) go to the log only, never to the page.
            Log::error('Failed to save health check-up.', [
                'patient_id' => $patient->id,
                'pharmacist_id' => Auth::id(),
                'exception' => $e,
            ]);

            return back()
                ->with('error', __('The check-up could not be saved. Please try again, or contact the administrator if it keeps happening.'))
                ->withInput();
        }
    }

    // Edit a saved check-up (e.g. to correct a typo such as cholesterol 48 instead of 4.8).
    public function edit($checkupId)
    {
        $checkup = $this->findOwnCheckup($checkupId);
        $checkup->load(['patient.user', 'pharmacist', 'editor']);

        return view('pharmacist.checkups.edit', [
            'checkup' => $checkup,
            'patient' => $checkup->patient,
            'metrics' => self::METRICS,
            'reportSources' => self::REPORT_SOURCES,
        ]);
    }

    public function update(Request $request, $checkupId)
    {
        $checkup = $this->findOwnCheckup($checkupId);
        $validated = $request->validate(self::readingRules(), array_map('__', self::READING_MESSAGES));

        // Normalise "120 / 80" to "120/80" like a fresh entry would read.
        if (! empty($validated['blood_pressure'])) {
            $validated['blood_pressure'] = preg_replace('/\s+/', '', $validated['blood_pressure']);
        }

        $checkup->fill($validated);
        $changes = [];
        foreach ($checkup->getDirty() as $field => $new) {
            $changes[$field] = ['from' => $checkup->getOriginal($field), 'to' => $new];
        }

        if ($changes === []) {
            return redirect()->route('pharmacist.patients.show', $checkup->patient_id)
                ->with('success', __('No changes were made to the check-up.'));
        }

        // The saved AI suggestion was based on the old readings; clear it so it can't mislead.
        $readingChanged = array_diff(array_keys($changes), ['notes', 'report_source']) !== [];
        $clearedAi = $readingChanged && filled($checkup->ai_suggestion);
        if ($clearedAi) {
            $checkup->ai_suggestion = null;
        }

        // Shown in the app as "Edited <date> by <name>" (full old -> new detail is in the log).
        $checkup->edited_by = $request->user()->id;
        $checkup->edited_at = now();

        $checkup->save();

        // Audit trail for clinical record changes (who, when, old -> new).
        Log::info('Health check-up edited.', [
            'checkup_id' => $checkup->id,
            'patient_id' => $checkup->patient_id,
            'edited_by' => $request->user()->id,
            'changes' => $changes,
        ]);

        $fields = implode(', ', array_map(
            fn ($f) => __(self::METRICS[$f][0] ?? str_replace('_', ' ', $f)),
            array_keys($changes)
        ));

        return redirect()->route('pharmacist.patients.show', $checkup->patient_id)
            ->with('success', __('Check-up of :date updated (:fields).', ['date' => $checkup->checkup_date->translatedFormat('d M Y'), 'fields' => $fields])
                . ($clearedAi ? ' ' . __('Its AI suggestion was cleared because the readings changed.') : ''));
    }

    /** A check-up belonging to one of the logged-in pharmacist's own patients, or 404. */
    private function findOwnCheckup($checkupId): HealthCheckup
    {
        return HealthCheckup::whereHas('patient', fn ($q) => $q->assignedTo(Auth::user()))
            ->findOrFail($checkupId);
    }
}
