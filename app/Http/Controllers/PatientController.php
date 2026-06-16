<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use App\Support\HealthSummaryPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\MedicalHistory; 
use App\Models\Medication;

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
        // Validate form data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'age' => 'required|integer',
            'gender' => 'required|string',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'face_descriptor' => 'nullable|string',
        ]);

        // Create 'User' account for the patient
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password123'), // Temporary password
            'requires_password_change' => true,
            'role' => 'patient',
        ]);

        // Calculate BMI automatically
        $height_in_meters = $request->height / 100;
        $bmi = $request->weight / ($height_in_meters * $height_in_meters);

        // Save health profile and face descriptor in 'patients' table
        Patient::create([
            'user_id' => $user->user_id ?? $user->id, 
            'pharmacist_id' => $request->user()->id,
            'age' => $request->age,
            'gender' => $request->gender,
            'weight' => $request->weight,
            'height' => $request->height,
            'bmi' => $bmi,
            'face_descriptor' => $request->filled('face_descriptor') ? $request->face_descriptor : null,
        ]);

        // Redirect directly to dashboard with success message
        return redirect()->route('pharmacist.dashboard')
                         ->with('success', 'Patient registered successfully!');
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
