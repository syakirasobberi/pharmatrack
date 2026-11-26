<?php

namespace Tests\Feature;

use App\Models\HealthCheckup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression (2026-11-26): two check-ups on the same day tied on checkup_date, so the
 * older one was shown as "latest" and trends compared them backwards.
 */
class CheckupOrderingTest extends TestCase
{
    use RefreshDatabase;

    private function twoSameDayCheckups(): array
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);
        $base = ['patient_id' => $patient->id, 'pharmacist_id' => $pharmacist->id, 'checkup_date' => today()];

        HealthCheckup::create($base + ['blood_pressure' => '135/85', 'blood_sugar' => 7.2]);
        HealthCheckup::create($base + ['blood_pressure' => '128/82', 'blood_sugar' => 6.4]);

        return [$pharmacist, $patient];
    }

    public function test_newest_entry_wins_when_dates_tie(): void
    {
        [, $patient] = $this->twoSameDayCheckups();

        $this->assertSame('128/82', $patient->healthCheckups()->first()->blood_pressure);
        $this->assertSame('128/82', $patient->latestCheckup->blood_pressure);
    }

    public function test_profile_and_summary_show_the_newest_reading(): void
    {
        [$pharmacist, $patient] = $this->twoSameDayCheckups();

        $this->actingAs($pharmacist)->get("/pharmacist/patients/{$patient->id}")
            ->assertOk()->assertSeeInOrder(['Latest Vitals', '128/82']);

        $this->actingAs($pharmacist)->get("/pharmacist/patients/{$patient->id}/summary")
            ->assertOk()->assertSeeInOrder(['Latest Blood Pressure', '128/82']);
    }

    public function test_patient_sees_newest_checkup_first(): void
    {
        [, $patient] = $this->twoSameDayCheckups();

        $this->actingAs($patient->user)->get('/patient/checkups')
            ->assertOk()->assertSeeInOrder(['128/82', '135/85']);
    }

    public function test_older_date_never_beats_a_newer_date(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);
        $base = ['patient_id' => $patient->id, 'pharmacist_id' => $pharmacist->id];

        HealthCheckup::create($base + ['checkup_date' => today(), 'blood_pressure' => '120/80']);
        HealthCheckup::create($base + ['checkup_date' => today()->subMonth(), 'blood_pressure' => '150/95']); // entered later, but older

        $this->assertSame('120/80', $patient->healthCheckups()->first()->blood_pressure);
    }
}
