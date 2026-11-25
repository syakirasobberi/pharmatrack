<?php

namespace App\Http\Middleware;

use App\Models\HealthCheckup;
use App\Models\Medication;
use App\Support\Audit;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records who looked at (or sent out) patient data. Changes to records are captured
 * separately by App\Observers\AuditObserver.
 */
class AuditAccess
{
    /** route name => [action, description] */
    private const ROUTES = [
        'pharmacist.patients.show' => ['patient.viewed', 'Viewed patient profile'],
        'pharmacist.patients.summary' => ['patient_summary.viewed', 'Viewed clinical summary'],
        'pharmacist.patients.edit' => ['patient.viewed', 'Opened patient details for editing'],
        'pharmacist.patients.medical.edit' => ['medical_history.viewed', 'Opened medical history'],
        'pharmacist.patients.riskPrediction' => ['risk_prediction.viewed', 'Viewed health risk prediction'],
        'pharmacist.patients.summary.download' => ['patient_summary.downloaded', 'Downloaded health summary PDF'],
        'pharmacist.patients.summary.email' => ['patient_summary.emailed', 'Emailed health summary PDF'],
        'pharmacist.medication.index' => ['medications.viewed', 'Viewed medications'],
        'pharmacist.medication.edit' => ['medications.viewed', 'Opened a medication for editing'],
        'pharmacist.checkups.create' => ['checkups.viewed', 'Opened check-up form'],
        'pharmacist.checkups.edit' => ['checkups.viewed', 'Opened a check-up for editing'],
        'pharmacist.quickScan.identify' => ['face.matched', 'Identified patient by Quick Scan (face)'],
        'kiosk.identify' => ['face.matched', 'Patient identified at the kiosk (face)'],
        'kiosk.summary' => ['patient_summary.viewed', 'Viewed own summary at the kiosk'],
        'kiosk.summary.download' => ['patient_summary.downloaded', 'Downloaded own summary PDF at the kiosk'],
        'patient.summary.download' => ['patient_summary.downloaded', 'Downloaded own summary PDF'],
        'patient.summary.email' => ['patient_summary.emailed', 'Emailed own summary PDF'],
    ];

    /** The same person opening the same record again within this many minutes is one entry. */
    private const REPEAT_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $name = $request->route()?->getName();
        if ($name && isset(self::ROUTES[$name]) && $this->succeeded($request, $response)) {
            [$action, $description] = self::ROUTES[$name];
            $patientId = $this->patientId($request, $response);

            if ($this->isFirstRecently($request, $action, $patientId)) {
                Audit::record($action, $description, $patientId);
            }
        }

        return $response;
    }

    private function succeeded(Request $request, Response $response): bool
    {
        if ($response instanceof JsonResponse) {
            return $response->getData(true)['matched'] ?? false;
        }
        if ($response instanceof RedirectResponse) {
            // POST actions (email) redirect back with a flash message.
            return $request->session()->has('success') && ! $request->session()->has('error');
        }

        return $response->isSuccessful();
    }

    private function patientId(Request $request, Response $response): ?int
    {
        $route = $request->route();

        if ($response instanceof JsonResponse) {
            return $response->getData(true)['patient_id'] ?? $request->session()->get('kiosk_patient_id');
        }
        if ($id = $route->parameter('id')) {
            return (int) $id;
        }
        if ($medication = $route->parameter('medication')) {
            return $medication instanceof Medication ? $medication->patient_id : Medication::whereKey($medication)->value('patient_id');
        }
        if ($checkup = $route->parameter('checkup')) {
            return $checkup instanceof HealthCheckup ? $checkup->patient_id : HealthCheckup::whereKey($checkup)->value('patient_id');
        }

        // Patient portal: their own record.
        return $request->user()?->role === 'patient'
            ? \App\Models\Patient::where('user_id', $request->user()->id)->value('id')
            : null;
    }

    private function isFirstRecently(Request $request, string $action, ?int $patientId): bool
    {
        $who = $request->user()?->id ?? 'ip:' . $request->ip();

        try {
            return Cache::add("audit-seen:{$who}:{$action}:{$patientId}", true, now()->addMinutes(self::REPEAT_MINUTES));
        } catch (\Throwable) {
            return true;
        }
    }
}
