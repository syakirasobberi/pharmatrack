<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\HealthRiskPredictionController;
use App\Http\Controllers\HealthCheckupController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\KioskPinController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// EN / BM toggle: remember the choice for this visit and go back to the page.
Route::get('/lang/{locale}', function (string $locale) {
    session(['locale' => $locale]);
    return redirect()->back(fallback: route('welcome'));
})->whereIn('locale', \App\Http\Middleware\SetLocale::SUPPORTED)->name('locale.switch');

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
    Route::post('/medications/{medication}/dose', [KioskController::class, 'logDose'])
        ->whereNumber('medication')
        ->middleware('throttle:kiosk-write')
        ->name('kiosk.medications.dose');
});

Route::get('/dashboard', function () {
    return match (request()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'pharmacist' => redirect()->route('pharmacist.dashboard'),
        'patient' => redirect()->route('patient.dashboard'),
        default => view('dashboard'),
    };
})->middleware(['auth', 'force_password_change', 'verified'])->name('dashboard');

// Staff two-step sign-in (EnsureTwoFactor sends staff here until the emailed code is entered).
Route::middleware('auth')->group(function () {
    Route::get('/two-factor', [\App\Http\Controllers\Auth\TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [\App\Http\Controllers\Auth\TwoFactorController::class, 'verify'])->middleware('throttle:10,1')->name('two-factor.verify');
    Route::post('/two-factor/resend', [\App\Http\Controllers\Auth\TwoFactorController::class, 'resend'])->middleware('throttle:5,1')->name('two-factor.resend');
});

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
    Route::patch('/admin/pharmacists/{user}/deactivate', [AdminController::class, 'deactivatePharmacist'])->name('admin.pharmacists.deactivate');
    Route::patch('/admin/pharmacists/{user}/reactivate', [AdminController::class, 'reactivatePharmacist'])->name('admin.pharmacists.reactivate');
    Route::get('/admin/patients', [AdminController::class, 'patients'])->name('admin.patients.index');
    Route::patch('/admin/patients/{patient}/pharmacist', [AdminController::class, 'assignPatient'])->name('admin.patients.assign');
    Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports.index');
    Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings.index');
    Route::patch('/admin/settings/app-name', [AdminController::class, 'updateAppName'])->name('admin.settings.updateAppName');
    Route::get('/admin/audit-log', [AuditLogController::class, 'index'])->name('admin.audit.index');
    Route::get('/admin/audit-log/export', [AuditLogController::class, 'export'])->name('admin.audit.export');
    Route::post('/admin/backups', [AdminController::class, 'createBackup'])->middleware('throttle:5,1')->name('admin.backups.create');
    Route::get('/admin/backups/{file}', [AdminController::class, 'downloadBackup'])->name('admin.backups.download');
});

Route::middleware(['auth', 'force_password_change', 'role:pharmacist'])->group(function () {
    // The dashboard view loads its own small, limited queries (recent patients, alerts).
    Route::view('/pharmacist/dashboard', 'pharmacist.dashboard')->name('pharmacist.dashboard');

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
    Route::get('/pharmacist/checkups/{checkup}/edit', [HealthCheckupController::class, 'edit'])->whereNumber('checkup')->name('pharmacist.checkups.edit');
    Route::patch('/pharmacist/checkups/{checkup}', [HealthCheckupController::class, 'update'])->whereNumber('checkup')->name('pharmacist.checkups.update');

    Route::get('/pharmacist/quick-scan', [PatientController::class, 'quickScan'])->name('pharmacist.quickScan');
    Route::post('/pharmacist/quick-scan/identify', [PatientController::class, 'quickScanIdentify'])
        ->middleware('throttle:120,1')
        ->name('pharmacist.quickScan.identify');
    Route::post('/pharmacist/patients/update-face', [PatientController::class, 'updateExistingFace'])->name('pharmacist.patients.updateFace');
    Route::delete('/pharmacist/patients/{id}/face', [PatientController::class, 'deleteExistingFace'])->name('pharmacist.patients.deleteFace');
    Route::post('/pharmacist/patients/{id}/face-consent', [PatientController::class, 'recordFaceConsent'])->name('pharmacist.patients.faceConsent');
    Route::get('/pharmacist/patients/{id}/medications', [MedicationController::class, 'index'])->name('pharmacist.medication.index');
    Route::post('/pharmacist/patients/{id}/medications', [MedicationController::class, 'store'])->name('pharmacist.medication.store');
    Route::get('/pharmacist/medications/{medication}/edit', [MedicationController::class, 'edit'])->whereNumber('medication')->name('pharmacist.medication.edit');
    Route::patch('/pharmacist/medications/{medication}', [MedicationController::class, 'update'])->whereNumber('medication')->name('pharmacist.medication.update');
    Route::patch('/pharmacist/medications/{medication}/stop', [MedicationController::class, 'stop'])->whereNumber('medication')->name('pharmacist.medication.stop');
    Route::patch('/pharmacist/medications/{medication}/resume', [MedicationController::class, 'resume'])->whereNumber('medication')->name('pharmacist.medication.resume');
    Route::post('/pharmacist/medications/{medication}/doses', [MedicationController::class, 'logDose'])->whereNumber('medication')->name('pharmacist.medication.doses.store');
    Route::post('/pharmacist/medications/{medication}/refill', [MedicationController::class, 'refill'])->whereNumber('medication')->name('pharmacist.medication.refill');
    Route::post('/pharmacist/medications/{medication}/review', [MedicationController::class, 'review'])->whereNumber('medication')->name('pharmacist.medication.review');
    Route::delete('/pharmacist/medications/{medication}/doses', [MedicationController::class, 'removeDose'])->whereNumber('medication')->name('pharmacist.medication.doses.destroy');
    Route::patch('/pharmacist/patients/{id}/archive', [PatientController::class, 'archive'])->name('pharmacist.patients.archive');
    Route::patch('/pharmacist/patients/{id}/restore', [PatientController::class, 'restore'])->name('pharmacist.patients.restore');
    Route::post('/pharmacist/patients/{id}/send-reminder', [PatientController::class, 'sendCheckupReminder'])->name('pharmacist.patients.sendReminder');

    Route::post('/api/generate-ai-suggestion', [AiController::class, 'generateSuggestion'])->name('api.ai.suggestion');
    Route::post('/api/extract-report', [AiController::class, 'extractReport'])
        ->middleware('throttle:15,1')
        ->name('api.ai.extractReport');
});

Route::middleware(['auth', 'force_password_change', 'role:patient'])->group(function () {
    Route::get('/patient/dashboard', function () {
        $patient = \App\Models\Patient::with(['currentMedications', 'healthCheckups', 'medicalHistory'])
            ->where('user_id', auth()->id())
            ->first();

        return view('patient.dashboard', compact('patient'));
    })->name('patient.dashboard');

    Route::get('/patient/medications', [PatientPortalController::class, 'medications'])->name('patient.medications');
    Route::post('/patient/medications', [PatientPortalController::class, 'storeMedication'])->name('patient.medications.store');
    Route::patch('/patient/medications/{medication}', [PatientPortalController::class, 'updateMedication'])->name('patient.medications.update');
    Route::post('/patient/medications/{medication}/doses', [PatientPortalController::class, 'logDose'])->name('patient.medications.doses.store');
    Route::post('/patient/medications/{medication}/refill-request', [PatientPortalController::class, 'requestRefill'])->name('patient.medications.refillRequest');
    Route::delete('/patient/medications/{medication}/doses', [PatientPortalController::class, 'removeDose'])->name('patient.medications.doses.destroy');
    Route::get('/patient/checkups', [PatientPortalController::class, 'checkups'])->name('patient.checkups');
    Route::get('/patient/download-summary', [PatientPortalController::class, 'downloadSummary'])->name('patient.summary.download');
    Route::post('/patient/email-summary', [PatientPortalController::class, 'emailSummary'])->name('patient.summary.email');
    Route::delete('/patient/face', [PatientPortalController::class, 'withdrawFaceConsent'])->name('patient.face.withdraw');
    Route::get('/patient/notifications', [PatientPortalController::class, 'notifications'])->name('patient.notifications.index');
    Route::post('/patient/notifications/{id}/read', [PatientPortalController::class, 'markNotificationRead'])->name('patient.notifications.read');
});
