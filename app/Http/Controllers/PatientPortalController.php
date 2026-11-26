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
    /** PDPA: the patient withdraws consent to face recognition; the face data is deleted at once. */
    public function withdrawFaceConsent(Request $request)
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();

        $back = redirect()->to(route('profile.edit') . '#face-recognition');

        if (! $patient->hasFace()) {
            return $back->with('face_status', __('There is no face data stored for you.'));
        }

        $patient->removeFace();
        \App\Support\Audit::record('patient.face_consent_withdrawn', 'Patient withdrew face-recognition consent; face data deleted', $patient->id, $patient);

        return $back->with('face_status', __('Your face data has been deleted. The kiosk will no longer recognise you — you can still log in with your email, and you can ask your pharmacist to register your face again at any time.'));
    }

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

            return back()->with('summary_error', __('The PDF could not be sent right now. Please try again later.'));
        }

        return back()->with('summary_success', __('Your health summary PDF has been sent to :email.', ['email' => $recipient]));
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
        ], [], self::fieldNames());

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
            ->with('success', __('Medication added successfully.'));
    }

    public function updateMedication(Request $request, Medication $medication)
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();

        abort_unless($medication->patient_id === $patient->id, 403);

        if ($medication->stopped_at) {
            return redirect()->route('patient.medications')
                ->with('error', __(':name was stopped by your pharmacist, so it can no longer be updated. Please contact them if you are still taking it.', ['name' => $medication->name]));
        }

        $validated = $request->validate([
            'last_taken' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:255',
        ], [], self::fieldNames());

        // Doses are logged with the "I took it" buttons now; the date field is only
        // applied if a form still sends it, so a notes-only update keeps last_taken.
        $medication->update(array_merge(
            ['notes' => $validated['notes'] ?? null],
            $request->filled('last_taken') ? ['last_taken' => $validated['last_taken']] : [],
        ));

        return redirect()
            ->route('patient.medications')
            ->with('success', __('Medication update saved.'));
    }

    /** "I took it" — log a dose for today or one of the previous 6 days. */
    public function logDose(Request $request, Medication $medication)
    {
        $medication = $this->ownCurrentMedication($request, $medication);
        if (! $medication instanceof Medication) {
            return $medication;
        }

        $day = $this->validatedDoseDay($request, $medication);
        $medication->recordDose($day, 'patient', $request->user());

        return redirect()->route('patient.medications')
            ->with('success', $day->isToday()
                ? __('Logged: you took :name today.', ['name' => $medication->name])
                : __('Logged: you took :name on :date.', ['name' => $medication->name, 'date' => $day->translatedFormat('d M')]));
    }

    /** Ask the pharmacist for a refill; shows on the pharmacist's dashboard until they refill. */
    public function requestRefill(Request $request, Medication $medication)
    {
        $medication = $this->ownCurrentMedication($request, $medication);
        if (! $medication instanceof Medication) {
            return $medication;
        }

        if ($medication->refill_requested_at) {
            return redirect()->route('patient.medications')
                ->with('success', __('You already requested a refill of :name on :date. Your pharmacist will prepare it.', ['name' => $medication->name, 'date' => $medication->refill_requested_at->translatedFormat('d M Y')]));
        }

        $medication->forceFill(['refill_requested_at' => now()])->save();

        return redirect()->route('patient.medications')
            ->with('success', __('Refill of :name requested. Your pharmacist will prepare it — please collect it at the pharmacy.', ['name' => $medication->name]));
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
            ->with('success', __('Removed the :name dose for :date.', ['name' => $medication->name, 'date' => $day->translatedFormat('d M')]));
    }

    /** The patient's own medication that is still current, or a redirect explaining why not. */
    private function ownCurrentMedication(Request $request, Medication $medication): Medication|\Illuminate\Http\RedirectResponse
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($medication->patient_id === $patient->id, 403);

        if (! $medication->isCurrent()) {
            return redirect()->route('patient.medications')
                ->with('error', __(':name is no longer current, so doses can\'t be logged for it.', ['name' => $medication->name]));
        }

        return $medication;
    }

    /** The dose day from the request (today if none). When logging ($forLogging given), it may not be before the medicine started. */
    private function validatedDoseDay(Request $request, ?Medication $forLogging = null): Carbon
    {
        $validated = $request->validate([
            'taken_on' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:' . today()->subDays(6)->toDateString()],
        ], [
            'taken_on.after_or_equal' => __('You can only log doses for the past 7 days.'),
            'taken_on.before_or_equal' => __('You cannot log a dose in the future.'),
        ]);

        $day = Carbon::parse($validated['taken_on'] ?? today())->startOfDay();

        $firstDay = $forLogging?->firstDoseDay();
        if ($firstDay && $day->lt($firstDay)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'taken_on' => __('You started :name on :date, so you can\'t log a dose before that.', ['name' => $forLogging->name, 'date' => $firstDay->translatedFormat('d M')]),
            ]);
        }

        return $day;
    }

    /** Readable field names for validation messages (EN / BM). */
    private static function fieldNames(): array
    {
        return [
            'name' => __('medication name'),
            'dosage' => __('dosage'),
            'frequency' => __('how to take'),
            'last_taken' => __('latest taken date'),
            'notes' => __('note'),
        ];
    }

    public function checkups(Request $request)
    {
        $patient = Patient::with([
            'healthCheckups' => fn ($query) => $query->latest('checkup_date')->latest('id'),
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

        return redirect()->back()->with('success', __('Notification marked as read.'));
    }

    private function summaryPatient(Request $request): Patient
    {
        return Patient::with([
            'user',
            'healthCheckups' => fn ($query) => $query->latest('checkup_date')->latest('id'),
            'medicalHistory',
            'currentMedications',
        ])->where('user_id', $request->user()->id)->firstOrFail();
    }
}
