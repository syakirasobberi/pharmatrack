<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PatientPortalController extends Controller
{
    /**
     * Download the patient's own health summary as an HTML file.
     */
    public function downloadSummary(Request $request)
    {
        $patient = Patient::with([
            'user',
            'healthCheckups' => fn ($q) => $q->latest('checkup_date'),
            'medicalHistory',
            'medications',
        ])->where('user_id', $request->user()->id)->firstOrFail();

        $filename = 'my-health-summary-' . now()->format('Y-m-d') . '.html';

        return response()
            ->view('pharmacist.patients.summary-download', compact('patient'))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Show the medication reminders page.
     */
    public function medications(Request $request)
    {
        $patient = Patient::with('medications')
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return view('patient.medications', compact('patient'));
    }

    public function checkups(Request $request)
    {
        $patient = Patient::with([
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
        ])->where('user_id', $request->user()->id)->firstOrFail();

        return view('patient.checkups', compact('patient'));
    }

}
