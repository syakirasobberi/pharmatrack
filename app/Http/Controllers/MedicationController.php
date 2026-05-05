<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medication;
use App\Models\Patient;
use Carbon\Carbon;

class MedicationController extends Controller
{
    public function index($id)
    {
        $patient = Patient::assignedTo(request()->user())->with('user')->findOrFail($id);
        $medications = $patient->medications()->latest()->get();

        return view('medications.index', compact('patient', 'medications'));
    }

    public function store(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dosage' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'last_taken' => 'required|date',
        ]);

        Medication::create([
            'patient_id' => $patient->id,
            'name' => $validated['name'],
            'dosage' => $validated['dosage'],
            'frequency' => $validated['dosage'],
            'notes' => $validated['notes'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'last_taken' => $validated['last_taken'],
        ]);

        return redirect()
            ->route('pharmacist.medication.index', $patient->id)
            ->with('success', 'Medication saved successfully.');
    }
}
