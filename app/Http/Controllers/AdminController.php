<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
use App\Models\HealthCheckup;
use App\Models\Medication;
use Illuminate\Support\Facades\Hash;

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
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount('assignedPatients')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $totalPharmacists = User::where('role', 'pharmacist')->count();
        $passwordPrompts = User::where('role', 'pharmacist')->where('requires_password_change', true)->count();

        return view('admin.pharmacists.index', compact('pharmacists', 'search', 'totalPharmacists', 'passwordPrompts'));
    }

    public function patients(Request $request)
    {
        $search = $request->string('search')->toString();

        $patients = Patient::with(['user', 'pharmacist', 'healthCheckups'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('pharmacist', function ($pharmacistQuery) use ($search) {
                    $pharmacistQuery->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $totalPatients = Patient::count();
        $patientsWithBiometrics = Patient::whereNotNull('face_descriptor')->where('face_descriptor', '!=', '')->count();
        $unassignedPatients = Patient::whereNull('pharmacist_id')->count();

        return view('admin.patients.index', compact('patients', 'search', 'totalPatients', 'patientsWithBiometrics', 'unassignedPatients'));
    }

    public function reports()
    {
        $totalUsers = User::count();
        $totalPharmacists = User::where('role', 'pharmacist')->count();
        $totalPatients = Patient::count();
        $totalCheckups = HealthCheckup::count();

        $highRiskCheckups = HealthCheckup::with('patient.user')
            ->where(function ($query) {
                $query->where('blood_sugar', '>=', 5.6)
                    ->orWhere('cholesterol', '>=', 5.2);
            })
            ->latest('checkup_date')
            ->take(10)
            ->get();

        $medicationFollowUps = Medication::with('patient.user')
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

        return view('admin.settings.index', compact('roleCounts', 'settings'));
    }

    public function updateAppName(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:60',
        ]);

        $envPath = base_path('.env');
        abort_unless(is_writable($envPath), 500, 'The .env file is not writable.');

        $envContents = file_get_contents($envPath);
        $appName = $this->formatEnvValue($validated['app_name']);

        if (preg_match('/^APP_NAME=.*$/m', $envContents)) {
            $envContents = preg_replace('/^APP_NAME=.*$/m', 'APP_NAME=' . $appName, $envContents);
        } else {
            $envContents = rtrim($envContents) . PHP_EOL . 'APP_NAME=' . $appName . PHP_EOL;
        }

        file_put_contents($envPath, $envContents);

        return back()->with('success', 'Application name updated successfully.');
    }

    public function storePharmacist(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
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
