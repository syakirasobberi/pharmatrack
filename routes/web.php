<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\HealthRiskPredictionController;
use App\Http\Controllers\HealthCheckupController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\KioskPinController;
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

// Kiosk: the browser computes a face descriptor and the server does the matching,
// so stored biometric data and patient names are never sent to the client.
Route::post('/kiosk/identify', [KioskController::class, 'identify'])
    ->middleware('throttle:kiosk-identify')
    ->name('kiosk.identify');

// Kiosk summary routes: only reachable after a successful server-side face match (10-minute window).
Route::middleware('kiosk.session')->prefix('kiosk/summary/{id}')->whereNumber('id')->group(function () {
    Route::get('/', [KioskController::class, 'summary'])->name('kiosk.summary');
    Route::get('/download', [KioskController::class, 'download'])->name('kiosk.summary.download');
    Route::post('/medical-history', [KioskController::class, 'updateMedical'])
        ->middleware('throttle:kiosk-write')
        ->name('kiosk.medical.update');
    Route::post('/checkups', [KioskController::class, 'storeCheckup'])
        ->middleware('throttle:kiosk-write')
        ->name('kiosk.checkups.store');
});

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
    Route::put('/profile/kiosk-pin', [KioskPinController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('profile.kiosk-pin.update');
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
    Route::post('/pharmacist/patients/{id}/summary/email', [PatientController::class, 'emailSummary'])->name('pharmacist.patients.summary.email');
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
    Route::post('/patient/email-summary', [PatientPortalController::class, 'emailSummary'])->name('patient.summary.email');
    Route::get('/patient/notifications', [PatientPortalController::class, 'notifications'])->name('patient.notifications.index');
    Route::post('/patient/notifications/{id}/read', [PatientPortalController::class, 'markNotificationRead'])->name('patient.notifications.read');
});
