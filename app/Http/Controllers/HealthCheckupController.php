<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\HealthCheckup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HealthCheckupController extends Controller
{
    // Paparkan borang Health Check-up untuk pesakit yang dipilih
    public function create($id)
    {
        $patient = Patient::assignedTo(Auth::user())->with('user')->findOrFail($id);
        return view('pharmacist.checkups.create', compact('patient'));
    }

    // Simpan data Health Check-up ke dalam database
    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'checkup_date'   => 'required|date',
            'patient_weight' => 'nullable|required_with:patient_height|numeric|min:1|max:300',
            'patient_height' => 'nullable|required_with:patient_weight|numeric|min:1|max:250',
            'blood_pressure' => 'nullable|string',
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
            'report_source'  => 'nullable|string',
            'notes'          => 'nullable|string|max:2000',
            'ai_suggestion'  => 'nullable|string|max:5000',
        ]);

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
                ->with('success', 'Health checkup record saved successfully!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to save: ' . $e->getMessage())
                ->withInput();
        }
    }
}
