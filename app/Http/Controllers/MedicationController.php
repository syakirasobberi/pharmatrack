<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medication;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;

class MedicationController extends Controller
{
    /**
     * End date is optional everywhere: blank = ongoing (long-term medicines like metformin
     * have no end date, and a made-up one would later mark them "Completed").
     * When editing, start and latest-dose dates are optional too, because medications added
     * by patients in their portal have none.
     */
    private static function rules(Request $request, bool $editing): array
    {
        return [
            'name' => 'required|string|max:255',
            'dosage' => 'required|string|max:255',
            'frequency' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'start_date' => [$editing ? 'nullable' : 'required', 'date'],
            'end_date' => ['nullable', 'date', ...($request->filled('start_date') ? ['after_or_equal:start_date'] : [])],
            'last_taken' => [$editing ? 'nullable' : 'required', 'date', 'before_or_equal:today'],
        ];
    }

    private const MESSAGES = [
        'end_date.after_or_equal' => 'The end date cannot be before the start date.',
        'last_taken.before_or_equal' => 'The latest dose date cannot be in the future.',
    ];

    public function index($id)
    {
        $patient = Patient::assignedTo(request()->user())->with('user')->findOrFail($id);
        $all = $patient->medications()->latest()->get();

        // Current first (what the patient takes now), then stopped/completed as history.
        $medications = $all->filter->isCurrent()->values();
        $pastMedications = $all->reject->isCurrent()->values();

        return view('medications.index', compact('patient', 'medications', 'pastMedications'));
    }

    public function store(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->findOrFail($id);

        $validated = $request->validate(self::rules($request, editing: false), self::MESSAGES);

        Medication::create([
            'patient_id' => $patient->id,
            'name' => $validated['name'],
            'dosage' => $validated['dosage'],
            'frequency' => ($validated['frequency'] ?? null) ?: $validated['dosage'],
            'notes' => $validated['notes'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'last_taken' => $validated['last_taken'],
        ]);

        return redirect()
            ->route('pharmacist.medication.index', $patient->id)
            ->with('success', 'Medication saved successfully.');
    }

    public function edit($medicationId)
    {
        $medication = $this->findOwnMedication($medicationId);
        $medication->load('patient.user');

        return view('medications.edit', ['medication' => $medication, 'patient' => $medication->patient]);
    }

    public function update(Request $request, $medicationId)
    {
        $medication = $this->findOwnMedication($medicationId);
        $validated = $request->validate(self::rules($request, editing: true), self::MESSAGES);
        // Keep the stored frequency if the field was left blank, so a no-change save writes nothing.
        $validated['frequency'] = ($validated['frequency'] ?? null) ?: ($medication->frequency ?: $validated['dosage']);

        $medication->fill($validated);
        $changes = [];
        foreach ($medication->getDirty() as $field => $new) {
            $changes[$field] = ['from' => $medication->getOriginal($field), 'to' => $new];
        }

        if ($changes === []) {
            return redirect()->route('pharmacist.medication.index', $medication->patient_id)
                ->with('success', 'No changes were made to ' . $medication->name . '.');
        }

        $medication->save();

        Log::info('Medication edited.', [
            'medication_id' => $medication->id,
            'patient_id' => $medication->patient_id,
            'edited_by' => $request->user()->id,
            'changes' => $changes,
        ]);

        return redirect()->route('pharmacist.medication.index', $medication->patient_id)
            ->with('success', $medication->name . ' updated.');
    }

    // Stop = the patient should no longer take it. Kept as history (not deleted).
    public function stop(Request $request, $medicationId)
    {
        $medication = $this->findOwnMedication($medicationId);

        $validated = $request->validate([
            'stopped_reason' => 'required|string|max:255',
        ], [
            'stopped_reason.required' => 'Please give a reason for stopping ' . $medication->name . '.',
        ]);

        if ($medication->stopped_at) {
            return back()->with('success', $medication->name . ' was already stopped.');
        }

        $medication->forceFill([
            'stopped_at' => now(),
            'stopped_reason' => $validated['stopped_reason'],
        ])->save();

        Log::info('Medication stopped.', [
            'medication_id' => $medication->id,
            'patient_id' => $medication->patient_id,
            'stopped_by' => $request->user()->id,
            'reason' => $validated['stopped_reason'],
        ]);

        return redirect()->route('pharmacist.medication.index', $medication->patient_id)
            ->with('success', $medication->name . ' stopped. It is kept under Past Medications.');
    }

    // Undo a stop made by mistake.
    public function resume(Request $request, $medicationId)
    {
        $medication = $this->findOwnMedication($medicationId);

        $medication->forceFill(['stopped_at' => null, 'stopped_reason' => null])->save();

        Log::info('Medication resumed.', [
            'medication_id' => $medication->id,
            'patient_id' => $medication->patient_id,
            'resumed_by' => $request->user()->id,
        ]);

        $note = $medication->isCurrent() ? '' : ' Its end date has passed, so it still shows as Completed — edit the end date if it should continue.';

        return redirect()->route('pharmacist.medication.index', $medication->patient_id)
            ->with('success', $medication->name . ' resumed.' . $note);
    }

    /** A medication of one of the logged-in pharmacist's own (non-archived) patients, or 404. */
    private function findOwnMedication($medicationId): Medication
    {
        return Medication::whereHas('patient', fn ($q) => $q->assignedTo(request()->user()))
            ->findOrFail($medicationId);
    }
}
