<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\HealthCheckup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HealthCheckupController extends Controller
{
    // Paparkan borang Health Check-up untuk pesakit yang dipilih
    public function create($id)
    {
        $patient = Patient::with('user')->findOrFail($id);
        return view('pharmacist.checkups.create', compact('patient'));
    }

    // Simpan data Health Check-up ke dalam database
    public function store(Request $request, $id)
    {
        $request->validate([
            'blood_pressure' => 'required|string',
            'blood_sugar' => 'required|numeric',
            'cholesterol' => 'required|numeric',
            'checkup_date' => 'required|date',
        ]);

        HealthCheckup::create([
            'patient_id' => $id,
            'pharmacist_id' => Auth::id(), // ID ahli farmasi yang sedang login
            'blood_pressure' => $request->blood_pressure,
            'blood_sugar' => $request->blood_sugar,
            'cholesterol' => $request->cholesterol,
            'checkup_date' => $request->checkup_date,
        ]);

        return redirect()->route('pharmacist.dashboard')->with('success', 'Data pemeriksaan kesihatan berjaya direkodkan!');
    }
}