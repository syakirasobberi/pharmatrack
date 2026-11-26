<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
use App\Models\HealthCheckup;
use App\Models\Medication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Kira statistik keseluruhan sistem PharmaTrack
        // Nota: Andaikan awak ada kolum 'role' dalam table users
        $totalPharmacists = User::where('role', 'pharmacist')->count(); 
        $totalPatients = Patient::count();
        $totalCheckups = HealthCheckup::count();

        // Tarik senarai staf (pharmacist) yang terbaru mendaftar
        $recentPharmacists = User::where('role', 'pharmacist')->latest()->take(5)->get();

        return view('admin.dashboard', compact('totalPharmacists', 'totalPatients', 'totalCheckups', 'recentPharmacists'));
    }

    public function pharmacists(Request $request)
    {
        $search = $request->string('search')->toString();

        $pharmacists = User::where('role', 'pharmacist')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->withCount('assignedPatients')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $totalPharmacists = User::where('role', 'pharmacist')->count();
        $passwordPrompts = User::where('role', 'pharmacist')->where('requires_password_change', true)->count();
        $deactivatedCount = User::where('role', 'pharmacist')->whereNotNull('deactivated_at')->count();
        // Candidates to take over a deactivated pharmacist's patients.
        $activePharmacists = User::where('role', 'pharmacist')->active()->orderBy('name')->get(['id', 'name']);

        return view('admin.pharmacists.index', compact(
            'pharmacists', 'search', 'totalPharmacists', 'passwordPrompts', 'deactivatedCount', 'activePharmacists'
        ));
    }

    /**
     * Switch a pharmacist's account off (no deletion: check-ups keep pointing at them).
     * Their patients must move to another active pharmacist so nobody is left unmanaged.
     */
    public function deactivatePharmacist(Request $request, User $user)
    {
        abort_unless($user->role === 'pharmacist', 404);

        if ($user->isDeactivated()) {
            return back()->with('success', "{$user->name} is already deactivated.");
        }

        $patientCount = $user->assignedPatients()->withArchived()->count(); // archived ones move too

        $validated = $request->validate([
            'transfer_to' => [
                $patientCount > 0 ? 'required' : 'nullable',
                'integer',
                Rule::notIn([$user->id]),
                Rule::exists('users', 'id')->where('role', 'pharmacist')->whereNull('deactivated_at'),
            ],
        ], [
            'transfer_to.required' => "{$user->name} has {$patientCount} patient(s). Choose who takes them over before deactivating.",
            'transfer_to.exists' => 'Please choose an active pharmacist to take over the patients.',
            'transfer_to.not_in' => 'Choose a different pharmacist to take over the patients.',
        ]);

        $to = $patientCount > 0 ? User::find($validated['transfer_to']) : null;

        DB::transaction(function () use ($user, $to) {
            if ($to) {
                $user->assignedPatients()->withArchived()->update(['pharmacist_id' => $to->id]);
            }
            $user->forceFill(['deactivated_at' => now()])->save();
        });

        Log::info('Pharmacist deactivated by admin.', [
            'pharmacist_id' => $user->id,
            'patients_moved' => $patientCount,
            'moved_to_pharmacist_id' => $to?->id,
            'admin_id' => $request->user()->id,
        ]);

        return back()->with('success', "{$user->name} has been deactivated and can no longer log in."
            . ($to ? " Their {$patientCount} patient(s) now belong to {$to->name}." : ''));
    }

    public function reactivatePharmacist(Request $request, User $user)
    {
        abort_unless($user->role === 'pharmacist', 404);

        $user->forceFill(['deactivated_at' => null])->save();

        Log::info('Pharmacist reactivated by admin.', ['pharmacist_id' => $user->id, 'admin_id' => $request->user()->id]);

        return back()->with('success', "{$user->name} has been reactivated and can log in again. Patients are not moved back automatically.");
    }

    public function patients(Request $request)
    {
        $search = $request->string('search')->toString();
        $onlyUnassigned = $request->boolean('unassigned');

        $patients = Patient::with(['user', 'pharmacist', 'healthCheckups'])
            ->when($search, function ($query) use ($search) {
                // Grouped so it combines correctly with the "unassigned only" filter.
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('pharmacist', function ($pharmacistQuery) use ($search) {
                        $pharmacistQuery->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($onlyUnassigned, fn ($query) => $query->whereNull('pharmacist_id'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $totalPatients = Patient::count();
        $patientsWithBiometrics = Patient::whereNotNull('face_descriptor')->where('face_descriptor', '!=', '')->count();
        // PDPA: faces registered before consent was recorded.
        $facesWithoutConsent = Patient::whereNotNull('face_descriptor')->where('face_descriptor', '!=', '')->whereNull('face_consent_at')->count();
        $unassignedPatients = Patient::whereNull('pharmacist_id')->count();
        // Only active pharmacists can receive patients.
        $activePharmacists = User::where('role', 'pharmacist')->active()->orderBy('name')->get(['id', 'name']);

        return view('admin.patients.index', compact(
            'patients', 'search', 'onlyUnassigned', 'totalPatients', 'patientsWithBiometrics', 'facesWithoutConsent', 'unassignedPatients', 'activePharmacists'
        ));
    }

    // Assign (or move) one patient to an active pharmacist.
    public function assignPatient(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'pharmacist_id' => ['required', 'integer', Rule::exists('users', 'id')
                ->where('role', 'pharmacist')->whereNull('deactivated_at')],
        ], [
            'pharmacist_id.exists' => 'Please choose an active pharmacist.',
        ]);

        $patient->load(['user', 'pharmacist']);
        $from = $patient->pharmacist?->name ?? 'Unassigned';

        if ((int) $patient->pharmacist_id === (int) $validated['pharmacist_id']) {
            return back()->with('success', "{$patient->user->name} is already assigned to {$from}.");
        }

        $fromId = $patient->pharmacist_id;
        $patient->update(['pharmacist_id' => $validated['pharmacist_id']]);
        $to = User::find($validated['pharmacist_id'])->name;

        Log::info('Patient reassigned by admin.', [
            'patient_id' => $patient->id,
            'from_pharmacist_id' => $fromId,
            'to_pharmacist_id' => (int) $validated['pharmacist_id'],
            'admin_id' => $request->user()->id,
        ]);

        return back()->with('success', "{$patient->user->name} moved from {$from} to {$to}.");
    }

    public function reports()
    {
        $totalUsers = User::count();
        $totalPharmacists = User::where('role', 'pharmacist')->count();
        $totalPatients = Patient::count();
        $totalCheckups = HealthCheckup::count();

        $highRiskCheckups = HealthCheckup::with('patient.user')
            ->whereHas('patient') // skip archived patients
            ->where(function ($query) {
                $query->where('blood_sugar', '<', 3.9)
                    ->orWhere('blood_sugar', '>', 6.0)
                    ->orWhere('cholesterol', '>=', 5.2);
            })
            ->latest('checkup_date')->latest('id')
            ->take(10)
            ->get();

        $medicationFollowUps = Medication::with('patient.user')
            ->whereHas('patient') // skip archived patients
            ->current()
            ->where(function ($query) {
                $query->whereNull('last_taken')
                    ->orWhereDate('last_taken', '<', today()->subDays(7))
                    ->orWhereBetween('end_date', [today(), today()->addDays(7)]);
            })
            ->latest()
            ->take(10)
            ->get();

        $monthlyCheckups = HealthCheckup::selectRaw('YEAR(checkup_date) as year, MONTH(checkup_date) as month, COUNT(*) as total')
            ->groupByRaw('YEAR(checkup_date), MONTH(checkup_date)')
            ->orderByRaw('YEAR(checkup_date) desc, MONTH(checkup_date) desc')
            ->take(6)
            ->get();

        return view('admin.reports.index', compact(
            'totalUsers',
            'totalPharmacists',
            'totalPatients',
            'totalCheckups',
            'highRiskCheckups',
            'medicationFollowUps',
            'monthlyCheckups'
        ));
    }

    public function settings()
    {
        $roleCounts = User::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $settings = [
            'app_name' => config('app.name'),
            'environment' => config('app.env'),
            'timezone' => config('app.timezone'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $backups = \App\Support\DatabaseBackup::all();

        return view('admin.settings.index', compact('roleCounts', 'settings', 'backups'));
    }

    /** "Back up now" on System Settings (the daily backup also runs automatically). */
    public function createBackup()
    {
        try {
            $path = \App\Support\DatabaseBackup::create();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.settings.index')->with('error', 'The backup could not be created: ' . $e->getMessage());
        }

        \App\Support\Audit::record('backup.created', 'Created database backup ' . basename($path));

        return redirect()->route('admin.settings.index')->with('success', 'Backup created: ' . basename($path) . '. Download a copy to keep it off the server.');
    }

    /** A backup holds every patient record and password hash: admins only, and every download is audited. */
    public function downloadBackup(string $file)
    {
        $path = \App\Support\DatabaseBackup::path($file);
        abort_if($path === null, 404);

        \App\Support\Audit::record('backup.downloaded', 'Downloaded database backup ' . $file);

        return response()->download($path, $file, ['Cache-Control' => 'no-store']);
    }

    public function updateAppName(Request $request)
    {
        $validated = $request->validate([
            // Plain characters only: $ and \ have special meaning in .env files and in regex replacements.
            'app_name' => ['required', 'string', 'max:60', "regex:/^[\\pL\\pN .,&'()!_-]+$/u"],
        ], [
            'app_name.regex' => 'The application name may only contain letters, numbers, spaces and . , & \' ( ) ! _ -',
        ]);

        $envPath = base_path('.env');

        if (! is_writable($envPath)) {
            return back()->with('error', 'The application name could not be saved because the server does not allow changes to the .env file.');
        }

        if (app()->configurationIsCached()) {
            return back()->with('error', 'Configuration is cached on this server, so a new name would not take effect. Run "php artisan config:clear" first.');
        }

        $envContents = file_get_contents($envPath);
        $line = 'APP_NAME=' . $this->formatEnvValue($validated['app_name']);

        if (preg_match('/^APP_NAME=.*$/m', $envContents)) {
            // Callback form: the replacement is inserted literally (no $1 / \1 back-references).
            $envContents = preg_replace_callback('/^APP_NAME=.*$/m', fn () => $line, $envContents, 1);
        } else {
            $envContents = rtrim($envContents) . PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($envPath, $envContents, LOCK_EX);

        return back()->with('success', 'Application name updated successfully.');
    }

    public function storePharmacist(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'role' => 'pharmacist',
            'requires_password_change' => true,
        ]);

        return back()
            ->with('success', 'Pharmacist account created. The staff member must change their password on first login.');
    }

    public function requirePasswordChange(User $user)
    {
        abort_unless($user->role === 'pharmacist', 404);

        $user->forceFill([
            'requires_password_change' => true,
        ])->save();

        return back()
            ->with('success', 'Password change prompt has been enabled for ' . $user->name . '.');
    }

    private function formatEnvValue(string $value): string
    {
        $value = trim($value);

        if ($value === '' || preg_match('/\s|#|"|\'/', $value)) {
            return '"' . addcslashes($value, "\\\"") . '"';
        }

        return $value;
    }
}
