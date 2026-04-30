<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
use App\Models\HealthCheckup;

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
}