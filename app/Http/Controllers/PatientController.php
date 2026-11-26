<?php

namespace App\Http\Controllers;

use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\PatientAccountSetupNotification;
use App\Support\Audit;
use App\Support\FaceMatch;
use App\Support\HealthSummaryPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PatientController extends Controller
{
    // 1. Display all patients (Index Page)
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $showArchived = $request->boolean('archived');

        // Archived patients are hidden by default; ?archived=1 lists only them (with Restore).
        $patients = ($showArchived ? Patient::onlyArchived() : Patient::query())
            ->assignedTo($request->user())
            ->with(['user', 'pharmacist'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($patientQuery) use ($search) {
                    $patientQuery->where('id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString(); // keep ?search= on page links

        $archivedCount = Patient::onlyArchived()->assignedTo($request->user())->count();

        return view('pharmacist.patients.index', compact('patients', 'search', 'showArchived', 'archivedCount'));
    }

    /**
     * Archive: the patient is hidden from lists, Quick Scan, the kiosk and reminders, and
     * cannot log in. Nothing is deleted; Restore brings everything back.
     */
    public function archive(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->with('user')->findOrFail($id);

        $validated = $request->validate(['archive_reason' => 'nullable|string|max:255']);

        DB::transaction(function () use ($patient) {
            $patient->forceFill(['archived_at' => now()])->save();
            if ($patient->user?->exists) {
                $patient->user->forceFill(['deactivated_at' => now()])->save();
            }
        });

        Log::info('Patient archived.', [
            'patient_id' => $patient->id,
            'archived_by' => $request->user()->id,
            'reason' => $validated['archive_reason'] ?? null,
        ]);

        return redirect()->route('pharmacist.patients.index')
            ->with('success', "{$patient->user->name} has been archived. Find them under \"Archived patients\" to restore.");
    }

    public function restore(Request $request, $id)
    {
        $patient = Patient::onlyArchived()->assignedTo($request->user())->with('user')->findOrFail($id);

        DB::transaction(function () use ($patient) {
            $patient->forceFill(['archived_at' => null])->save();
            if ($patient->user?->exists) {
                $patient->user->forceFill(['deactivated_at' => null])->save();
            }
        });

        Log::info('Patient restored.', ['patient_id' => $patient->id, 'restored_by' => $request->user()->id]);

        return redirect()->route('pharmacist.patients.show', $patient->id)
            ->with('success', "{$patient->user->name} has been restored and can log in again.");
    }
    
    // 2. Display the registration form (Includes Camera UI)
    public function create()
    {
        return view('pharmacist.patients.create');
    }

    // 3. Save new patient data, with optional face biometric data
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'age' => ['required', 'integer', 'min:0', 'max:130'],
                'gender' => ['required', Rule::in(['Male', 'Female'])],
                'weight' => ['required', 'numeric', 'min:1', 'max:999.99'],
                'height' => ['required', 'numeric', 'min:1', 'max:999.99'],
                'face_descriptor' => ['nullable', 'string', 'max:10000', function ($attribute, $value, $fail) {
                    // Must be a face-api.js descriptor, or the kiosk/quick scan can't match it.
                    if (! FaceMatch::isValid(json_decode($value, true))) {
                        $fail('The captured face data is invalid. Please scan the face again.');
                    }
                }],
                // PDPA: a face may only be saved with the patient's recorded consent.
                // exclude_without: no face captured → consent not needed (accepted would otherwise always run).
                'face_consent' => ['exclude_without:face_descriptor', 'accepted'],
            ],
            [
                'face_consent.accepted' => 'Tick the consent box: the patient must agree before their face is registered.',
                'email.unique' => 'This email address is already registered. Please use a different email for the new patient.',
            ]
        );

        // Random, unguessable starting password; the patient sets their own via the emailed link.
        $temporaryPassword = Str::password(12, symbols: false);

        $patient = DB::transaction(function () use ($request, $validated, $temporaryPassword) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($temporaryPassword),
                'requires_password_change' => true,
                'role' => 'patient',
            ]);

            $heightInMeters = $validated['height'] / 100;

            return Patient::create([
                'user_id' => $user->id,
                'pharmacist_id' => $request->user()->id,
                'age' => $validated['age'],
                'gender' => $validated['gender'],
                'weight' => $validated['weight'],
                'height' => $validated['height'],
                'bmi' => $validated['weight'] / ($heightInMeters * $heightInMeters),
                'face_descriptor' => $validated['face_descriptor'] ?? null,
            ]);
        });

        if ($patient->hasFace()) {
            $patient->recordFaceConsent($request->user());
            Audit::record('patient.face_consent_given', 'Face registered with the patient\'s consent', $patient->id, $patient);
        }

        $redirect = redirect()->route('pharmacist.patients.show', $patient->id);

        try {
            $user = $patient->user;
            $user->notify(new PatientAccountSetupNotification(Password::broker()->createToken($user)));
        } catch (Throwable $exception) {
            Log::error('Failed to send patient account setup email.', [
                'patient_id' => $patient->id,
                'exception' => $exception,
            ]);

            // Email failed: show the temporary password once so it can be handed to the patient in person.
            return $redirect->with('error', "Patient registered, but the setup email could not be sent. Give the patient this temporary password in person: {$temporaryPassword} (they must change it on first login).");
        }

        return $redirect->with('success', "Patient registered. An email has been sent to {$validated['email']} so they can set their own password.");
    }

    // 4. Display full patient profile
    public function show($id)
    {
        $patient = Patient::assignedTo(request()->user())->with([
            'user',
            'pharmacist',
            'healthCheckups' => fn ($q) => $q->latest('checkup_date')->latest('id'),
            'healthCheckups.editor', // "Edited <date> by <name>" in the history table
            'medicalHistory',
            'currentMedications',
        ])->findOrFail($id);
        return view('pharmacist.patients.show', compact('patient'));
    }

    public function edit($id)
    {
        $patient = Patient::assignedTo(request()->user())->with(['user', 'pharmacist'])->findOrFail($id);

        return view('pharmacist.patients.edit', compact('patient'));
    }

    public function update(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->with('user')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($patient->user_id)],
            'age' => ['required', 'integer', 'min:0', 'max:130'],
            'gender' => ['required', 'string', Rule::in(['Male', 'Female'])],
            'height' => ['required', 'numeric', 'min:1'],
            'weight' => ['required', 'numeric', 'min:1'],
        ]);

        $patient->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $heightInMeters = $validated['height'] / 100;

        $patient->update([
            'age' => $validated['age'],
            'gender' => $validated['gender'],
            'height' => $validated['height'],
            'weight' => $validated['weight'],
            'bmi' => $validated['weight'] / ($heightInMeters * $heightInMeters),
        ]);

        return redirect()->route('pharmacist.patients.show', $patient->id)
            ->with('success', 'Patient profile updated successfully!');
    }

    public function summary($id)
    {
        $patient = $this->buildSummaryPatient($id);

        // ML risk estimate (PHP decision tree) from BMI + the latest check-up.
        $prediction = app(HealthRiskPredictionController::class)->predictForPatient($patient);

        return view('pharmacist.patients.summary', compact('patient', 'prediction'));
    }

    public function downloadSummary($id)
    {
        $patient = $this->buildSummaryPatient($id);
        $filename = 'patient-health-summary-' . $patient->id . '-' . now()->format('Y-m-d') . '.pdf';

        return response(HealthSummaryPdf::make($patient))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function emailSummary(Request $request, $id)
    {
        $patient = $this->buildSummaryPatient($id);
        $recipient = $patient->user->email;

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'This patient does not have a valid email address.');
        }

        $filename = 'patient-health-summary-' . $patient->id . '-' . now()->format('Y-m-d') . '.pdf';

        try {
            Mail::send('emails.pharmacist-patient-health-summary', [
                'patient' => $patient,
                'pharmacist' => $request->user(),
            ], function ($message) use ($patient, $recipient, $filename) {
                $message
                    ->to($recipient, $patient->user->name)
                    ->subject('Your PharmaTrack Health Summary')
                    ->attachData(HealthSummaryPdf::make($patient), $filename, [
                        'mime' => 'application/pdf',
                    ]);
            });
        } catch (Throwable $exception) {
            Log::error('Failed to email patient health summary from pharmacist portal.', [
                'patient_id' => $patient->id,
                'pharmacist_id' => $request->user()->id,
                'exception' => $exception,
            ]);

            return back()->with('error', 'The PDF could not be sent right now. Please check the server email configuration and try again.');
        }

        return back()->with('success', "The health summary PDF has been sent to {$recipient}.");
    }

    protected function buildSummaryPatient($id)
    {
        return Patient::assignedTo(request()->user())->with([
            'user',
            'pharmacist',
            'healthCheckups' => fn ($query) => $query->latest('checkup_date')->latest('id'),
            'medicalHistory',
            'currentMedications',
        ])->findOrFail($id);
    }

    // 5. Open Medical Record form
    public function editMedical($id)
    {
        $patient = Patient::assignedTo(request()->user())->with(['medicalHistory', 'pharmacist'])->findOrFail($id);
        return view('pharmacist.patients.medical', compact('patient'));
    }

    // 6. Update Medical records to database
    public function updateMedical(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->findOrFail($id);

        $validated = $request->validate([
            'diabetes'       => ['required', 'string', 'max:100'],
            'allergies'      => ['nullable', 'string', 'max:255'],
            'drug_allergies' => ['nullable', 'string', 'max:255'],
            'others'         => ['nullable', 'string', 'max:255'],
            'others_custom'  => ['nullable', 'required_if:others,Other (specify)', 'string', 'max:255'],
        ], [
            'others_custom.required_if' => 'Please describe the other condition.',
        ]);

        // Medications are added on the Medications page, which records start/end dates.
        MedicalHistory::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'hypertension'  => $patient->medicalHistory?->hypertension ?? 'None', // preserved, not editable from form
                'diabetes'      => $validated['diabetes'],
                'allergies'     => $validated['allergies'] ?? null,
                'drug_allergies' => $validated['drug_allergies'] ?? null,
                'others'        => ($validated['others'] ?? null) === 'Other (specify)'
                                    ? $validated['others_custom']
                                    : ($validated['others'] ?? null),
            ]
        );

        return redirect()->route('pharmacist.patients.show', $patient->id)
                         ->with('success', 'Medical records updated successfully!');
    }

    // 7. Function to update face biometric for existing patients
    public function updateExistingFace(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'descriptor' => ['required', 'array', 'size:128'],
            'descriptor.*' => ['required', 'numeric'],
            'consent' => ['accepted'],   // PDPA: the patient agreed before this capture
        ], [
            'consent.accepted' => 'The patient must agree to face recognition before their face is saved.',
        ]);

        $patient = Patient::assignedTo($request->user())->find($validated['patient_id']);

        if (!$patient) {
            return response()->json(['status' => 'error', 'message' => 'Patient not found.'], 404);
        }

        $patient->face_descriptor = json_encode(array_map('floatval', $validated['descriptor']));
        $patient->save();
        $patient->recordFaceConsent($request->user());
        Audit::record('patient.face_consent_given', 'Face registered with the patient\'s consent', $patient->id, $patient);

        return response()->json(['status' => 'success', 'message' => 'Face data successfully updated!']);
    }

    public function deleteExistingFace(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->findOrFail($id);

        $patient->removeFace();
        Audit::record('patient.face_removed', 'Face data and face-recognition consent removed by the pharmacist', $patient->id, $patient);

        return redirect()
            ->route('pharmacist.patients.show', $patient->id)
            ->with('success', 'Patient face data removed successfully.');
    }

    /** Record consent for a face that was registered before consent was recorded (PDPA). */
    public function recordFaceConsent(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->findOrFail($id);
        $profile = route('pharmacist.patients.show', $patient->id) . '#face-registration';   // land on the message, not the page top

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), ['consent' => ['accepted']], [
            'consent.accepted' => 'Tick the box to confirm the patient agreed, or remove the face instead.',
        ]);
        if ($validator->fails()) {
            return redirect()->to($profile)->withErrors($validator);
        }

        abort_unless($patient->hasFace(), 404);

        $patient->recordFaceConsent($request->user());
        Audit::record('patient.face_consent_given', 'Consent recorded for an already registered face', $patient->id, $patient);

        return redirect()->to(route('pharmacist.patients.show', $patient->id) . '#face-registration')
            ->with('success', 'Face-recognition consent recorded.');
    }

    public function quickScan()
    {
        // Only a count is sent to the page; the face data itself stays on the server.
        $faceCount = Patient::assignedTo(request()->user())
            ->whereNotNull('face_descriptor')
            ->where('face_descriptor', '!=', '')
            ->count();

        return view('pharmacist.patients.quick-scan', compact('faceCount'));
    }

    // Quick Scan: same rules as the kiosk (several frames, every frame must match), but only
    // among this pharmacist's own patients. The page asks the pharmacist to confirm the name.
    public function quickScanIdentify(Request $request)
    {
        $validated = $request->validate([
            'descriptors' => ['required', 'array', 'min:3', 'max:5'],
            'descriptors.*' => ['required', 'array', 'size:128'],
            'descriptors.*.*' => ['required', 'numeric', 'between:-2,2'],
        ]);

        $frames = array_map(fn ($d) => array_map('floatval', array_values($d)), $validated['descriptors']);
        $match = FaceMatch::identify(Patient::assignedTo($request->user()), $frames);

        if ($match['status'] !== 'matched') {
            return response()->json(['matched' => false, 'reason' => $match['status']]);
        }

        $patient = Patient::with('user')->findOrFail($match['id']);

        return response()->json([
            'matched' => true,
            'name' => $patient->user->name,
            'patient_id' => $patient->id,
            // Lower is a closer match; shown so staff can judge borderline matches.
            'distance' => round($match['distance'], 3),
            'redirect' => route('pharmacist.patients.summary', $patient->id),
        ]);
    }

    public function sendCheckupReminder($id)
    {
        $patient = Patient::assignedTo(request()->user())->with('user')->findOrFail($id);

        if (! $patient->user?->exists) {
            return redirect()->back()->with('error', 'Patient user account not found.');
        }

        try {
            $patient->user->notify(new \App\Notifications\RoutineCheckupReminderNotification($patient));
        } catch (Throwable $exception) {
            Log::error('Failed to send routine check-up reminder.', [
                'patient_id' => $patient->id,
                'exception' => $exception,
            ]);

            return redirect()->back()->with('error', 'The reminder could not be sent right now. Please check the server email configuration and try again.');
        }

        return redirect()->back()->with('success', 'Routine check-up reminder sent to ' . $patient->user->name . ' via email and system notification.');
    }
}
