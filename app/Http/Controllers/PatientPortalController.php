<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Models\Patient;
use App\Support\Adherence;
use App\Support\DrugSafety;
use App\Support\HealthSummaryPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Throwable;

class PatientPortalController extends Controller
{
    /**
     * Download the patient's own health summary as a PDF file.
     */
    public function downloadSummary(Request $request)
    {
        $patient = $this->summaryPatient($request);

        $filename = 'my-health-summary-' . now()->format('Y-m-d') . '.pdf';

        return response(HealthSummaryPdf::make($patient))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Email the patient's own health summary PDF to their account email.
     */
    public function emailSummary(Request $request)
    {
        $patient = $this->summaryPatient($request);
        $recipient = $request->user()->email;
        $filename = 'my-health-summary-' . now()->format('Y-m-d') . '.pdf';

        try {
            Mail::send('emails.patient-health-summary', ['patient' => $patient], function ($message) use ($patient, $recipient, $filename) {
                $message
                    ->to($recipient, $patient->user->name)
                    ->subject('Your PharmaTrack Health Summary')
                    ->attachData(HealthSummaryPdf::make($patient), $filename, [
                        'mime' => 'application/pdf',
                    ]);
            });
        } catch (Throwable $exception) {
            Log::error('Failed to email patient health summary.', [
                'patient_id' => $patient->id,
                'user_id' => $request->user()->id,
                'exception' => $exception,
            ]);

            return back()->with('summary_error', 'The PDF could not be sent right now. Please try again later.');
        }

        return back()->with('summary_success', "Your health summary PDF has been sent to {$recipient}.");
    }

    /**
     * Show the medication reminders page.
     */
    public function medications(Request $request)
    {
        $patient = Patient::with(['medications' => fn ($query) => $query->latest('updated_at')])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $latestMedicationUpdate = $patient->medications->max('updated_at');
        $needsMedicationUpdate = $patient->medications->isNotEmpty()
            && (! $latestMedicationUpdate || Carbon::parse($latestMedicationUpdate)->lt(Carbon::today()->subMonths(6)));

        $adherence = Adherence::forPatient($patient, $patient->medications->filter->isCurrent()->values());

        return view('patient.medications', compact('patient', 'latestMedicationUpdate', 'needsMedicationUpdate', 'adherence'));
    }

    public function storeMedication(Request $request)
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dosage' => 'nullable|string|max:255',
            'frequency' => 'nullable|string|max:255',
            'last_taken' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:255',
        ]);

        $dosage = filled($validated['dosage'] ?? null) ? $validated['dosage'] : 'Not specified';

        // Check against what they already take before saving (the new record is not in the list yet).
        $warnings = DrugSafety::checkNew($validated['name'], $patient->currentMedications()->get(), $patient->medicalHistory);

        Medication::create([
            'patient_id' => $patient->id,
            'name' => $validated['name'],
            'dosage' => $dosage,
            'frequency' => filled($validated['frequency'] ?? null) ? $validated['frequency'] : $dosage,
            'last_taken' => $validated['last_taken'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Patients may record what they take, so it is saved, but they are warned and the
        // decision is left to the pharmacist (who sees the same warnings on the medication page).
        if ($warnings !== []) {
            Log::warning('Patient added a medication with safety warnings.', [
                'patient_id' => $patient->id,
                'medication' => $validated['name'],
                'warnings' => array_column($warnings, 'title'),
            ]);

            return redirect()
                ->route('patient.medications')
                ->with('patient_drug_warnings', ['name' => $validated['name'], 'warnings' => $warnings]);
        }

        return redirect()
            ->route('patient.medications')
            ->with('success', 'Medication added successfully.');
    }

    public function updateMedication(Request $request, Medication $medication)
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();

        abort_unless($medication->patient_id === $patient->id, 403);

        if ($medication->stopped_at) {
            return redirect()->route('patient.medications')
                ->with('error', "{$medication->name} was stopped by your pharmacist, so it can no longer be updated. Please contact them if you are still taking it.");
        }

        $validated = $request->validate([
            'last_taken' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:255',
        ]);

        // Doses are logged with the "I took it" buttons now; the date field is only
        // applied if a form still sends it, so a notes-only update keeps last_taken.
        $medication->update(array_merge(
            ['notes' => $validated['notes'] ?? null],
            $request->filled('last_taken') ? ['last_taken' => $validated['last_taken']] : [],
        ));

        return redirect()
            ->route('patient.medications')
            ->with('success', 'Medication update saved.');
    }

    /** "I took it" — log a dose for today or one of the previous 6 days. */
    public function logDose(Request $request, Medication $medication)
    {
        $medication = $this->ownCurrentMedication($request, $medication);
        if (! $medication instanceof Medication) {
            return $medication;
        }

        $day = $this->validatedDoseDay($request);
        $medication->recordDose($day, 'patient', $request->user());

        return redirect()->route('patient.medications')
            ->with('success', $day->isToday()
                ? "Logged: you took {$medication->name} today."
                : "Logged: you took {$medication->name} on {$day->format('d M')}.");
    }

    /** Undo a dose logged by mistake. */
    public function removeDose(Request $request, Medication $medication)
    {
        $medication = $this->ownCurrentMedication($request, $medication);
        if (! $medication instanceof Medication) {
            return $medication;
        }

        $day = $this->validatedDoseDay($request);
        $medication->removeDose($day);

        return redirect()->route('patient.medications')
            ->with('success', "Removed the {$medication->name} dose for {$day->format('d M')}.");
    }

    /** The patient's own medication that is still current, or a redirect explaining why not. */
    private function ownCurrentMedication(Request $request, Medication $medication): Medication|\Illuminate\Http\RedirectResponse
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($medication->patient_id === $patient->id, 403);

        if (! $medication->isCurrent()) {
            return redirect()->route('patient.medications')
                ->with('error', "{$medication->name} is no longer current, so doses can't be logged for it.");
        }

        return $medication;
    }

    private function validatedDoseDay(Request $request): Carbon
    {
        $validated = $request->validate([
            'taken_on' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:' . today()->subDays(6)->toDateString()],
        ], [
            'taken_on.after_or_equal' => 'You can only log doses for the past 7 days.',
            'taken_on.before_or_equal' => 'You cannot log a dose in the future.',
        ]);

        return Carbon::parse($validated['taken_on'] ?? today())->startOfDay();
    }

    public function checkups(Request $request)
    {
        $patient = Patient::with([
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
        ])->where('user_id', $request->user()->id)->firstOrFail();

        return view('patient.checkups', compact('patient'));
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        $unreadCount = $request->user()->unreadNotifications()->count();

        return view('patient.notifications', compact('notifications', 'unreadCount'));
    }

    public function markNotificationRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    private function summaryPatient(Request $request): Patient
    {
        return Patient::with([
            'user',
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
            'medicalHistory',
            'currentMedications',
        ])->where('user_id', $request->user()->id)->firstOrFail();
    }
}
