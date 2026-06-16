<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Models\Patient;
use App\Support\HealthSummaryPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PatientPortalController extends Controller
{
    /**
     * Download the patient's own health summary as a PDF file.
     */
    public function downloadSummary(Request $request)
    {
        $patient = Patient::with([
            'user',
            'healthCheckups' => fn ($q) => $q->latest('checkup_date'),
            'medicalHistory',
            'medications',
        ])->where('user_id', $request->user()->id)->firstOrFail();

        $filename = 'my-health-summary-' . now()->format('Y-m-d') . '.pdf';

        return response(HealthSummaryPdf::make($patient))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Show the medication reminders page.
     */
    public function medications(Request $request)
    {
        $patient = Patient::with(['medications' => fn ($query) => $query->latest('updated_at')])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $latestMedicationUpdate = $patient->medications->max('updated_at');
        $needsMedicationUpdate = $patient->medications->isNotEmpty()
            && (! $latestMedicationUpdate || Carbon::parse($latestMedicationUpdate)->lt(Carbon::today()->subMonths(6)));

        return view('patient.medications', compact('patient', 'latestMedicationUpdate', 'needsMedicationUpdate'));
    }

    public function storeMedication(Request $request)
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dosage' => 'nullable|string|max:255',
            'frequency' => 'nullable|string|max:255',
            'last_taken' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:255',
        ]);

        $dosage = filled($validated['dosage'] ?? null) ? $validated['dosage'] : 'Not specified';

        Medication::create([
            'patient_id' => $patient->id,
            'name' => $validated['name'],
            'dosage' => $dosage,
            'frequency' => filled($validated['frequency'] ?? null) ? $validated['frequency'] : $dosage,
            'last_taken' => $validated['last_taken'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('patient.medications')
            ->with('success', 'Medication added successfully.');
    }

    public function updateMedication(Request $request, Medication $medication)
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();

        abort_unless($medication->patient_id === $patient->id, 403);

        $validated = $request->validate([
            'last_taken' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:255',
        ]);

        $medication->update([
            'last_taken' => $validated['last_taken'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('patient.medications')
            ->with('success', 'Medication update saved.');
    }

    public function checkups(Request $request)
    {
        $patient = Patient::with([
            'healthCheckups' => fn ($query) => $query->latest('checkup_date'),
        ])->where('user_id', $request->user()->id)->firstOrFail();

        return view('patient.checkups', compact('patient'));
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        $unreadCount = $request->user()->unreadNotifications()->count();

        return view('patient.notifications', compact('notifications', 'unreadCount'));
    }

    public function markNotificationRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }
}
