<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\MedicalHistory; 
use App\Models\Medication;

class PatientController extends Controller
{
    // 1. Display all patients (Index Page)
    public function index()
    {
        $patients = Patient::assignedTo(request()->user())->with(['user', 'pharmacist'])->latest()->get();
        return view('pharmacist.patients.index', compact('patients'));
    }
    
    // 2. Display the registration form (Includes Camera UI)
    public function create()
    {
        return view('pharmacist.patients.create');
    }

    // 3. Save new patient data AND Face Biometric to the database
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
            'face_descriptor' => $request->face_descriptor, // <--- Data dari hidden input kamera
        ]);

        // Redirect directly to dashboard with success message
        return redirect()->route('pharmacist.dashboard')
                         ->with('success', 'Patient and Biometric Data registered successfully!');
    }

    // 4. Display full patient profile
    public function show($id)
    {
        $patient = Patient::assignedTo(request()->user())->with(['user', 'pharmacist', 'healthCheckups'])->findOrFail($id);
        return view('pharmacist.patients.show', compact('patient'));
    }

    public function summary($id)
    {
        $patient = $this->buildSummaryPatient($id);

        return view('pharmacist.patients.summary', compact('patient'));
    }

    public function downloadSummary($id)
    {
        $patient = $this->buildSummaryPatient($id);
        $filename = 'patient-health-summary-' . $patient->id . '.html';

        return response()
            ->view('pharmacist.patients.summary-download', compact('patient'))
            ->header('Content-Type', 'text/html; charset=UTF-8')
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
                'hypertension' => $request->hypertension,
                'diabetes' => $request->diabetes,
                'allergies' => $request->allergies,
                'drug_allergies' => $request->drug_allergies,
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
        $patientId = $request->input('patient_id');
        $descriptor = $request->input('descriptor');

        $patient = Patient::assignedTo($request->user())->find($patientId);

        if (!$patient) {
            return response()->json(['status' => 'error', 'message' => 'Patient not found.'], 404);
        }

        // Update the face_descriptor column
        $patient->face_descriptor = json_encode($descriptor);
        $patient->save();

        return response()->json(['status' => 'success', 'message' => 'Face data successfully updated!']);
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
}
