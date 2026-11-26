<?php

namespace Tests\Feature;

use App\Models\HealthCheckup;
use App\Models\Medication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every page opens (200) for its role, with real data on it, so a Blade or query
 * error on any page fails here instead of on the live site.
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private function patientWithData(\App\Models\User $pharmacist): \App\Models\Patient
    {
        $patient = $this->patientOf($pharmacist);
        $checkup = HealthCheckup::create([
            'patient_id' => $patient->id, 'pharmacist_id' => $pharmacist->id, 'checkup_date' => today(),
            'blood_pressure' => '135/85', 'heart_rate' => 78, 'blood_sugar' => 7.2, 'cholesterol' => 5.4, 'hba1c' => 6.8,
        ]);
        Medication::create([
            'patient_id' => $patient->id, 'name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'Twice daily',
            'start_date' => today()->subDays(5), 'last_taken' => today(), 'supply_until' => today()->addDays(30),
        ]);

        return $patient->fresh();
    }

    public function test_public_pages_open(): void
    {
        foreach (['/', '/login', '/forgot-password'] as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_admin_pages_open(): void
    {
        $this->patientWithData($this->pharmacist());
        $admin = $this->admin();

        foreach (['/admin/dashboard', '/admin/pharmacists', '/admin/patients', '/admin/reports',
            '/admin/audit-log', '/admin/settings', '/profile'] as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
        $this->actingAs($admin)->get('/admin/audit-log/export')->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_pharmacist_pages_open(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientWithData($pharmacist);
        $checkup = $patient->healthCheckups->first();
        $medication = $patient->medications()->first();

        foreach (['/pharmacist/dashboard', '/pharmacist/patients', '/pharmacist/patients?archived=1',
            '/pharmacist/patients/create', '/pharmacist/quick-scan', '/profile',
            "/pharmacist/patients/{$patient->id}", "/pharmacist/patients/{$patient->id}/edit",
            "/pharmacist/patients/{$patient->id}/medical", "/pharmacist/patients/{$patient->id}/checkup",
            "/pharmacist/patients/{$patient->id}/medications", "/pharmacist/patients/{$patient->id}/summary",
            "/pharmacist/checkups/{$checkup->id}/edit", "/pharmacist/medications/{$medication->id}/edit",
            "/pharmacist/patients/{$patient->id}/risk-prediction"] as $page) {
            $this->actingAs($pharmacist)->get($page)->assertOk();
        }
        $this->actingAs($pharmacist)->get("/pharmacist/patients/{$patient->id}/summary/download")
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_patient_pages_open(): void
    {
        $user = $this->patientWithData($this->pharmacist())->user;

        foreach (['/patient/dashboard', '/patient/medications', '/patient/checkups',
            '/patient/notifications', '/profile'] as $page) {
            $this->actingAs($user)->get($page)->assertOk();
        }
        $this->actingAs($user)->get('/patient/download-summary')
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_removed_test_page_stays_gone(): void
    {
        $this->actingAs($this->pharmacist())->get('/kamera-test')->assertNotFound();
    }
}
