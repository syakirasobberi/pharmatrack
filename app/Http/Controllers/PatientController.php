<?php

namespace App\Http\Controllers;

use App\Models\MedicalHistory;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\PatientAccountSetupNotification;
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

        $patients = Patient::assignedTo($request->user())
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
            ->get();

        return view('pharmacist.patients.index', compact('patients', 'search'));
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
                'face_descriptor' => ['nullable', 'string'],
            ],
            [
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
            'healthCheckups' => fn ($q) => $q->latest('checkup_date'),
            'medicalHistory',
            'medications',
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

    return view('pharmacist.patients.summary', compact('patient'));
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
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
            'medicalHistory',
            'medications',
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

        // Update or Create Medical History
        MedicalHistory::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'hypertension'  => $patient->medicalHistory?->hypertension ?? 'None', // preserved, not editable from form
                'diabetes'      => $request->diabetes,
                'allergies'     => $request->allergies,
                'drug_allergies' => $request->drug_allergies,
                'others'        => $request->others === 'Other (specify)'
                                    ? $request->others_custom
                                    : $request->others,
            ]
        );

        // Add New Medication 
        if ($request->filled('med_name')) {
            Medication::create([
                'patient_id' => $patient->id,
                'name' => $request->med_name,
                'dosage' => $request->med_dosage,
                'frequency' => $request->med_frequency,
            ]);
        }

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
        ]);

        $patient = Patient::assignedTo($request->user())->find($validated['patient_id']);

        if (!$patient) {
            return response()->json(['status' => 'error', 'message' => 'Patient not found.'], 404);
        }

        $patient->face_descriptor = json_encode(array_map('floatval', $validated['descriptor']));
        $patient->save();

        return response()->json(['status' => 'success', 'message' => 'Face data successfully updated!']);
    }

    public function deleteExistingFace(Request $request, $id)
    {
        $patient = Patient::assignedTo($request->user())->findOrFail($id);

        $patient->face_descriptor = null;
        $patient->save();

        return redirect()
            ->route('pharmacist.patients.show', $patient->id)
            ->with('success', 'Patient face data removed successfully.');
    }

    public function quickScan()
    {
        // Ambil hanya pesakit yang benar-benar mempunyai data wajah yang boleh digunakan
        $patients = Patient::assignedTo(request()->user())->with('user')
            ->whereNotNull('face_descriptor')
            ->where('face_descriptor', '!=', '')
            ->get(['id', 'user_id', 'face_descriptor']);

        return view('pharmacist.patients.quick-scan', compact('patients'));
    }

    public function sendCheckupReminder($id)
    {
        $patient = Patient::assignedTo(request()->user())->with('user')->findOrFail($id);

        if ($patient->user) {
            $patient->user->notify(new \App\Notifications\RoutineCheckupReminderNotification($patient));
            return redirect()->back()->with('success', 'Routine check-up reminder sent to ' . $patient->user->name . ' via email and system notification.');
        }

        return redirect()->back()->with('error', 'Patient user account not found.');
    }
}
