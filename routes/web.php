<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\HealthRiskPredictionController;
use App\Http\Controllers\HealthCheckupController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Models\HealthCheckup;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Support\HealthSummaryPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Public kiosk endpoint — returns only the data needed for face matching (no sensitive info)
Route::get('/kiosk/patients', function () {
    $patients = \App\Models\Patient::with('user')
        ->whereNotNull('face_descriptor')
        ->where('face_descriptor', '!=', '')
        ->get(['id', 'user_id', 'face_descriptor'])
        ->map(function ($p) {
            return [
                'id'              => $p->id,
                'name'            => $p->user?->name ?? 'Patient',
                'face_descriptor' => $p->face_descriptor,
            ];
        });
    return response()->json($patients);
})->name('kiosk.patients');

// Kiosk face-recognition authentication — sets a short-lived session token
Route::post('/kiosk/auth/{id}', function ($id) {
    $patient = \App\Models\Patient::findOrFail($id);
    session(['kiosk_patient_id' => $patient->id, 'kiosk_auth_at' => now()->timestamp]);
    return response()->json(['redirect' => route('kiosk.summary', $id)]);
})->name('kiosk.auth');

// Kiosk summary — accessible only if session was set by face recognition above
Route::get('/kiosk/summary/{id}', function ($id) {
    // Validate session: must exist and be less than 10 minutes old
    $kioskId  = session('kiosk_patient_id');
    $kioskAt  = session('kiosk_auth_at');
    $expired  = !$kioskAt || (now()->timestamp - $kioskAt) > 600;

    if ((string) $kioskId !== (string) $id || $expired) {
        session()->forget(['kiosk_patient_id', 'kiosk_auth_at']);
        return redirect()->route('welcome')->with('error', 'Session expired. Please scan your face again.');
    }

    $patient = \App\Models\Patient::with([
        'user',
        'healthCheckups' => fn ($q) => $q->latest('checkup_date'),
        'medicalHistory',
        'medications',
    ])->findOrFail($id);

    return view('kiosk.summary', compact('patient'));
})->name('kiosk.summary');

Route::get('/kiosk/summary/{id}/download', function ($id) {
    $kioskId  = session('kiosk_patient_id');
    $kioskAt  = session('kiosk_auth_at');
    $expired  = !$kioskAt || (now()->timestamp - $kioskAt) > 600;

    if ((string) $kioskId !== (string) $id || $expired) {
        session()->forget(['kiosk_patient_id', 'kiosk_auth_at']);
        return redirect()->route('welcome')->with('error', 'Session expired. Please scan your face again.');
    }

    $patient = Patient::with([
        'user',
        'pharmacist',
        'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
        'medicalHistory',
        'medications',
    ])->findOrFail($id);

    $filename = 'kiosk-health-summary-' . $patient->id . '-' . now()->format('Y-m-d') . '.pdf';

    return response(HealthSummaryPdf::make($patient))
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
})->name('kiosk.summary.download');

Route::post('/kiosk/summary/{id}/medical-history', function (Request $request, $id) {
    $kioskId  = session('kiosk_patient_id');
    $kioskAt  = session('kiosk_auth_at');
    $expired  = !$kioskAt || (now()->timestamp - $kioskAt) > 600;

    if ((string) $kioskId !== (string) $id || $expired) {
        session()->forget(['kiosk_patient_id', 'kiosk_auth_at']);
        return redirect()->route('welcome')->with('error', 'Session expired. Please scan your face again.');
    }

    $patient = Patient::with(['medicalHistory', 'pharmacist'])->findOrFail($id);
    $pharmacist = $patient->pharmacist;

    $validated = $request->validateWithBag('kioskMedical', [
        'hypertension' => ['required', 'string', 'max:255'],
        'diabetes' => ['required', 'string', 'max:255'],
        'allergies' => ['nullable', 'string', 'max:255'],
        'drug_allergies' => ['nullable', 'string', 'max:255'],
        'others' => ['nullable', 'string', 'max:255'],
        'pharmacist_code' => ['required', 'string'],
    ]);

    if (! $pharmacist || $pharmacist->role !== 'pharmacist' || ! Hash::check($validated['pharmacist_code'], $pharmacist->password)) {
        return back()
            ->withErrors(['pharmacist_code' => 'Invalid pharmacist code for this patient.'], 'kioskMedical')
            ->withInput($request->except('pharmacist_code'));
    }

    MedicalHistory::updateOrCreate(
        ['patient_id' => $patient->id],
        [
            'hypertension' => $validated['hypertension'],
            'diabetes' => $validated['diabetes'],
            'allergies' => $validated['allergies'] ?? null,
            'drug_allergies' => $validated['drug_allergies'] ?? null,
            'others' => $validated['others'] ?? null,
        ]
    );

    return redirect()
        ->route('kiosk.summary', $patient->id)
        ->with('kiosk_medical_success', 'Medical record updated successfully.');
})->name('kiosk.medical.update');

Route::post('/kiosk/summary/{id}/checkups', function (Request $request, $id) {
    $kioskId  = session('kiosk_patient_id');
    $kioskAt  = session('kiosk_auth_at');
    $expired  = !$kioskAt || (now()->timestamp - $kioskAt) > 600;

    if ((string) $kioskId !== (string) $id || $expired) {
        session()->forget(['kiosk_patient_id', 'kiosk_auth_at']);
        return redirect()->route('welcome')->with('error', 'Session expired. Please scan your face again.');
    }

    $patient = Patient::with('pharmacist')->findOrFail($id);
    $pharmacist = $patient->pharmacist;

    $validated = $request->validateWithBag('kioskCheckup', [
        'checkup_date' => ['required', 'date'],
        'report_source' => ['nullable', 'string', 'max:255'],
        'patient_weight' => ['nullable', 'required_with:patient_height', 'numeric', 'min:1', 'max:300'],
        'patient_height' => ['nullable', 'required_with:patient_weight', 'numeric', 'min:1', 'max:250'],
        'blood_pressure' => ['nullable', 'string', 'max:255'],
        'heart_rate' => ['nullable', 'integer', 'min:30', 'max:250'],
        'haemoglobin' => ['nullable', 'numeric', 'min:0', 'max:30'],
        'blood_sugar' => ['nullable', 'numeric', 'min:0', 'max:50'],
        'hba1c' => ['nullable', 'numeric', 'min:3', 'max:20'],
        'albumin_globulin_ratio' => ['nullable', 'numeric', 'min:0', 'max:10'],
        'alkaline_phosphatase' => ['nullable', 'numeric', 'min:0', 'max:2000'],
        'aspartate_transaminase' => ['nullable', 'numeric', 'min:0', 'max:2000'],
        'alanine_transaminase' => ['nullable', 'numeric', 'min:0', 'max:2000'],
        'gamma_glutamyl_transferase' => ['nullable', 'numeric', 'min:0', 'max:2000'],
        'sodium' => ['nullable', 'numeric', 'min:80', 'max:200'],
        'renal_glucose' => ['nullable', 'numeric', 'min:0', 'max:50'],
        'cholesterol' => ['nullable', 'numeric', 'min:0', 'max:30'],
        'ldl' => ['nullable', 'numeric', 'min:0', 'max:20'],
        'hdl' => ['nullable', 'numeric', 'min:0', 'max:10'],
        'notes' => ['nullable', 'string', 'max:2000'],
        'pharmacist_code' => ['required', 'string'],
    ]);

    if (! $pharmacist || $pharmacist->role !== 'pharmacist' || ! Hash::check($validated['pharmacist_code'], $pharmacist->password)) {
        return back()
            ->withErrors(['pharmacist_code' => 'Invalid pharmacist code for this patient.'], 'kioskCheckup')
            ->withInput($request->except('pharmacist_code'));
    }

    $checkupData = $validated;
    unset($checkupData['patient_weight'], $checkupData['patient_height'], $checkupData['pharmacist_code']);

    DB::transaction(function () use ($patient, $pharmacist, $validated, $checkupData) {
        if (isset($validated['patient_weight'], $validated['patient_height'])) {
            $heightInMeters = (float) $validated['patient_height'] / 100;

            $patient->update([
                'weight' => $validated['patient_weight'],
                'height' => $validated['patient_height'],
                'bmi' => $validated['patient_weight'] / ($heightInMeters * $heightInMeters),
            ]);
        }

        HealthCheckup::create(array_merge($checkupData, [
            'patient_id' => $patient->id,
            'pharmacist_id' => $pharmacist->id,
        ]));
    });

    return redirect()
        ->route('kiosk.summary', $patient->id)
        ->with('kiosk_checkup_success', 'New check-up recorded successfully.');
})->name('kiosk.checkups.store');

Route::get('/dashboard', function () {
    return match (request()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'pharmacist' => redirect()->route('pharmacist.dashboard'),
        'patient' => redirect()->route('patient.dashboard'),
        default => view('dashboard'),
    };
})->middleware(['auth', 'force_password_change', 'verified'])->name('dashboard');

Route::middleware(['auth', 'force_password_change'])->group(function () {
    Route::get('/force-password-change', [ForcePasswordChangeController::class, 'create'])->name('password.force-change');
    Route::post('/force-password-change', [ForcePasswordChangeController::class, 'store'])->name('password.force-change.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'force_password_change', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/pharmacists', [AdminController::class, 'pharmacists'])->name('admin.pharmacists.index');
    Route::post('/admin/pharmacists', [AdminController::class, 'storePharmacist'])->name('admin.pharmacists.store');
    Route::patch('/admin/pharmacists/{user}/require-password-change', [AdminController::class, 'requirePasswordChange'])->name('admin.pharmacists.requirePasswordChange');
    Route::get('/admin/patients', [AdminController::class, 'patients'])->name('admin.patients.index');
    Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports.index');
    Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings.index');
    Route::patch('/admin/settings/app-name', [AdminController::class, 'updateAppName'])->name('admin.settings.updateAppName');
});

Route::middleware(['auth', 'force_password_change', 'role:pharmacist'])->group(function () {
    Route::get('/pharmacist/dashboard', function () {
        $search = request('search');
        $totalPatients = Patient::assignedTo(request()->user())->count();

        $patients = Patient::assignedTo(request()->user())->with(['user', 'pharmacist'])
            ->when($search, function ($query, $searchTerm) {
                $query->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            })
            ->latest()
            ->get();

        return view('pharmacist.dashboard', compact('patients', 'totalPatients'));
    })->name('pharmacist.dashboard');

    Route::get('/pharmacist/patients', [PatientController::class, 'index'])->name('pharmacist.patients.index');
    Route::get('/pharmacist/patients/create', [PatientController::class, 'create'])->name('pharmacist.patients.create');
    Route::post('/pharmacist/patients', [PatientController::class, 'store'])->name('pharmacist.patients.store');
    Route::get('/pharmacist/patients/{id}/edit', [PatientController::class, 'edit'])->name('pharmacist.patients.edit');
    Route::patch('/pharmacist/patients/{id}', [PatientController::class, 'update'])->name('pharmacist.patients.update');
    Route::get('/pharmacist/patients/{id}', [PatientController::class, 'show'])->name('pharmacist.patients.show');
    Route::get('/pharmacist/patients/{id}/summary', [PatientController::class, 'summary'])->name('pharmacist.patients.summary');
    Route::post('/pharmacist/patients/{id}/ai-summary', [AiController::class, 'generatePatientSummary'])->name('pharmacist.patients.aiSummary');
    Route::get('/pharmacist/patients/{id}/risk-prediction', [HealthRiskPredictionController::class, 'show'])->name('pharmacist.patients.riskPrediction');
    Route::get('/pharmacist/patients/{id}/summary/download', [PatientController::class, 'downloadSummary'])->name('pharmacist.patients.summary.download');
    Route::get('/pharmacist/patients/{id}/medical', [PatientController::class, 'editMedical'])->name('pharmacist.patients.medical.edit');
    Route::post('/pharmacist/patients/{id}/medical', [PatientController::class, 'updateMedical'])->name('pharmacist.patients.medical.update');
    Route::get('/pharmacist/patients/{id}/checkup', [HealthCheckupController::class, 'create'])->name('pharmacist.checkups.create');
    Route::post('/pharmacist/patients/{id}/checkup', [HealthCheckupController::class, 'store'])->name('pharmacist.checkups.store');

    Route::get('/pharmacist/quick-scan', [PatientController::class, 'quickScan'])->name('pharmacist.quickScan');
    Route::post('/pharmacist/patients/update-face', [PatientController::class, 'updateExistingFace'])->name('pharmacist.patients.updateFace');
    Route::delete('/pharmacist/patients/{id}/face', [PatientController::class, 'deleteExistingFace'])->name('pharmacist.patients.deleteFace');
    Route::get('/pharmacist/patients/{id}/medications', [MedicationController::class, 'index'])->name('pharmacist.medication.index');
    Route::post('/pharmacist/patients/{id}/medications', [MedicationController::class, 'store'])->name('pharmacist.medication.store');
    Route::post('/pharmacist/patients/{id}/send-reminder', [PatientController::class, 'sendCheckupReminder'])->name('pharmacist.patients.sendReminder');

    Route::post('/api/generate-ai-suggestion', [AiController::class, 'generateSuggestion'])->name('api.ai.suggestion');
    Route::get('/kamera-test', function () {
        return view('kamera-test');
    })->name('pharmacist.kamera-test');
});

Route::middleware(['auth', 'force_password_change', 'role:patient'])->group(function () {
    Route::get('/patient/dashboard', function () {
        $patient = \App\Models\Patient::with(['medications', 'healthCheckups', 'medicalHistory'])
            ->where('user_id', auth()->id())
            ->first();

        return view('patient.dashboard', compact('patient'));
    })->name('patient.dashboard');

    Route::get('/patient/medications', [PatientPortalController::class, 'medications'])->name('patient.medications');
    Route::post('/patient/medications', [PatientPortalController::class, 'storeMedication'])->name('patient.medications.store');
    Route::patch('/patient/medications/{medication}', [PatientPortalController::class, 'updateMedication'])->name('patient.medications.update');
    Route::get('/patient/checkups', [PatientPortalController::class, 'checkups'])->name('patient.checkups');
    Route::get('/patient/download-summary', [PatientPortalController::class, 'downloadSummary'])->name('patient.summary.download');
    Route::get('/patient/notifications', [PatientPortalController::class, 'notifications'])->name('patient.notifications.index');
    Route::post('/patient/notifications/{id}/read', [PatientPortalController::class, 'markNotificationRead'])->name('patient.notifications.read');
});
