<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\HealthCheckup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'blood_pressure' => 'nullable|string',
            'heart_rate'     => 'nullable|integer|min:30|max:250',
            'spo2'           => 'nullable|integer|min:50|max:100',
            'weight'         => 'nullable|numeric|min:10|max:300',
            'height'         => 'nullable|numeric|min:50|max:250',
            'bmi'            => 'nullable|numeric|min:5|max:80',
            'blood_sugar'    => 'nullable|numeric|min:0|max:50',
            'hba1c'          => 'nullable|numeric|min:3|max:20',
            'cholesterol'    => 'nullable|numeric|min:0|max:30',
            'ldl'            => 'nullable|numeric|min:0|max:20',
            'hdl'            => 'nullable|numeric|min:0|max:10',
            'triglycerides'  => 'nullable|numeric|min:0|max:20',
            'report_source'  => 'nullable|string',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $patient = Patient::assignedTo($request->user())->findOrFail($id);

        try {
            HealthCheckup::create(array_merge($validated, [
                'patient_id'    => $patient->id,
                'pharmacist_id' => Auth::id(),
            ]));

            $patient->update(array_filter([
                'weight' => $validated['weight'] ?? null,
                'height' => $validated['height'] ?? null,
                'bmi' => $validated['bmi'] ?? null,
            ], fn ($value) => $value !== null));

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
