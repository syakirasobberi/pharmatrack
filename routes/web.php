<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\HealthCheckupController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Models\Patient;
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

Route::get('/dashboard', function () {
    return view('dashboard');
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
    Route::get('/pharmacist/patients/{id}', [PatientController::class, 'show'])->name('pharmacist.patients.show');
    Route::get('/pharmacist/patients/{id}/summary', [PatientController::class, 'summary'])->name('pharmacist.patients.summary');
    Route::get('/pharmacist/patients/{id}/summary/download', [PatientController::class, 'downloadSummary'])->name('pharmacist.patients.summary.download');
    Route::get('/pharmacist/patients/{id}/medical', [PatientController::class, 'editMedical'])->name('pharmacist.patients.medical.edit');
    Route::post('/pharmacist/patients/{id}/medical', [PatientController::class, 'updateMedical'])->name('pharmacist.patients.medical.update');
    Route::get('/pharmacist/patients/{id}/checkup', [HealthCheckupController::class, 'create'])->name('pharmacist.checkups.create');
    Route::post('/pharmacist/patients/{id}/checkup', [HealthCheckupController::class, 'store'])->name('pharmacist.checkups.store');

    Route::get('/pharmacist/quick-scan', [PatientController::class, 'quickScan'])->name('pharmacist.quickScan');
    Route::post('/pharmacist/patients/update-face', [PatientController::class, 'updateExistingFace'])->name('pharmacist.patients.updateFace');
    Route::get('/pharmacist/patients/{id}/medications', [MedicationController::class, 'index'])->name('pharmacist.medication.index');
    Route::post('/pharmacist/patients/{id}/medications', [MedicationController::class, 'store'])->name('pharmacist.medication.store');

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
    Route::get('/patient/download-summary', [PatientPortalController::class, 'downloadSummary'])->name('patient.summary.download');
});
