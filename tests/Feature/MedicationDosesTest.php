<?php

namespace Tests\Feature;

use App\Models\Medication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Adding medicines, logging and undoing doses, refill and stop/resume. */
class MedicationDosesTest extends TestCase
{
    use RefreshDatabase;

    private function medicine(\App\Models\Patient $patient, array $attributes = []): Medication
    {
        return Medication::create($attributes + [
            'patient_id' => $patient->id, 'name' => 'Amlodipine', 'dosage' => '5mg', 'frequency' => 'Once daily',
            'start_date' => today()->subDays(3), 'last_taken' => today()->subDay(),
        ]);
    }

    public function test_pharmacist_adds_a_medicine(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);

        $this->actingAs($pharmacist)->post("/pharmacist/patients/{$patient->id}/medications", [
            'name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'Twice daily',
            'start_date' => today()->toDateString(), 'last_taken' => today()->toDateString(), 'supply_days' => 30,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $medication = $patient->medications()->firstOrFail();
        $this->assertSame('Metformin', $medication->name);
        $this->assertTrue($medication->supply_until->isSameDay(today()->addDays(30)));
        $this->assertNotNull($medication->reviewed_at);
    }

    public function test_patient_logs_and_undoes_a_dose(): void
    {
        $patient = $this->patientOf($this->pharmacist());
        $medication = $this->medicine($patient);

        $this->actingAs($patient->user)->post("/patient/medications/{$medication->id}/doses")->assertRedirect();
        $this->assertTrue($medication->fresh()->last_taken->isToday());
        $this->assertSame(1, $medication->doses()->count());

        $this->actingAs($patient->user)->delete("/patient/medications/{$medication->id}/doses", [
            'taken_on' => today()->toDateString(),
        ])->assertRedirect();
        $this->assertSame(0, $medication->doses()->count());
    }

    /** Regression (2026-11-26): undoing today's dose erased a latest-dose date entered in the form. */
    public function test_undo_keeps_a_latest_dose_date_that_was_already_recorded(): void
    {
        $patient = $this->patientOf($this->pharmacist());
        $medication = $this->medicine($patient, ['last_taken' => today()]);

        $this->actingAs($patient->user)->post("/patient/medications/{$medication->id}/doses");
        $this->actingAs($patient->user)->delete("/patient/medications/{$medication->id}/doses", [
            'taken_on' => today()->toDateString(),
        ]);

        $this->assertTrue($medication->fresh()->last_taken?->isToday(), 'latest dose date was erased');
    }

    /** Regression (2026-11-26): a dose could be logged for a day before the medicine started. */
    public function test_dose_before_start_date_is_rejected(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);
        $medication = $this->medicine($patient, ['start_date' => today(), 'last_taken' => today()]);
        $before = today()->subDays(2)->toDateString();

        $this->actingAs($patient->user)->post("/patient/medications/{$medication->id}/doses", ['taken_on' => $before])
            ->assertSessionHasErrors('taken_on');
        $this->actingAs($pharmacist)->post("/pharmacist/medications/{$medication->id}/doses", ['taken_on' => $before])
            ->assertSessionHasErrors('taken_on', null, 'dose');

        $this->assertSame(0, $medication->doses()->count());
    }

    public function test_dose_in_the_future_is_rejected(): void
    {
        $patient = $this->patientOf($this->pharmacist());
        $medication = $this->medicine($patient);

        $this->actingAs($patient->user)->post("/patient/medications/{$medication->id}/doses", [
            'taken_on' => today()->addDay()->toDateString(),
        ])->assertSessionHasErrors('taken_on');
    }

    public function test_refill_adds_to_current_supply(): void
    {
        $pharmacist = $this->pharmacist();
        $medication = $this->medicine($this->patientOf($pharmacist), ['supply_until' => today()->addDays(10)]);

        $this->actingAs($pharmacist)->post("/pharmacist/medications/{$medication->id}/refill", ['days' => 30])
            ->assertRedirect();

        $this->assertTrue($medication->fresh()->supply_until->isSameDay(today()->addDays(40)));
    }

    public function test_stop_needs_a_reason_and_can_be_resumed(): void
    {
        $pharmacist = $this->pharmacist();
        $medication = $this->medicine($this->patientOf($pharmacist));

        $this->actingAs($pharmacist)->patch("/pharmacist/medications/{$medication->id}/stop", [])
            ->assertSessionHasErrors('stopped_reason');
        $this->assertTrue($medication->fresh()->isCurrent());

        $this->actingAs($pharmacist)->patch("/pharmacist/medications/{$medication->id}/stop", ['stopped_reason' => 'Side effects']);
        $this->assertFalse($medication->fresh()->isCurrent());

        $this->actingAs($pharmacist)->patch("/pharmacist/medications/{$medication->id}/resume");
        $this->assertTrue($medication->fresh()->isCurrent());
    }

    public function test_patient_cannot_log_doses_for_someone_elses_medicine(): void
    {
        $pharmacist = $this->pharmacist();
        $other = $this->medicine($this->patientOf($pharmacist));

        $this->actingAs($this->patientOf($pharmacist)->user)
            ->post("/patient/medications/{$other->id}/doses")
            ->assertForbidden();
    }

    public function test_patient_added_medicine_waits_for_pharmacist_review(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);

        $this->actingAs($patient->user)->post('/patient/medications', [
            'name' => 'Vitamin D', 'dosage' => '1000 IU', 'last_taken' => today()->toDateString(),
        ])->assertRedirect();
        $medication = $patient->medications()->firstOrFail();
        $this->assertTrue($medication->needsReview());

        $this->actingAs($pharmacist)->post("/pharmacist/medications/{$medication->id}/review")->assertRedirect();
        $this->assertFalse($medication->fresh()->needsReview());
    }
}
