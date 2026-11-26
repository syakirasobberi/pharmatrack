<?php

namespace Tests\Feature;

use App\Models\HealthCheckup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Registering, editing, check-ups, archiving and admin staff management. */
class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_pharmacist_registers_a_patient_who_gets_a_setup_email(): void
    {
        Notification::fake();
        $pharmacist = $this->pharmacist();

        $this->actingAs($pharmacist)->post('/pharmacist/patients', [
            'name' => 'Demo Patient', 'email' => 'demo.patient@example.test',
            'age' => 45, 'gender' => 'Female', 'height' => 160, 'weight' => 62,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $user = User::where('email', 'demo.patient@example.test')->firstOrFail();
        $this->assertSame('patient', $user->role);
        $this->assertTrue((bool) $user->requires_password_change);
        $this->assertSame($pharmacist->id, \App\Models\Patient::where('user_id', $user->id)->value('pharmacist_id'));
        Notification::assertSentTo($user, \App\Notifications\PatientAccountSetupNotification::class);
    }

    public function test_registering_with_a_used_email_fails(): void
    {
        $pharmacist = $this->pharmacist();
        $existing = $this->patientOf($pharmacist)->user;

        $this->actingAs($pharmacist)->post('/pharmacist/patients', [
            'name' => 'Copy', 'email' => $existing->email, 'age' => 30, 'gender' => 'Male', 'height' => 170, 'weight' => 70,
        ])->assertSessionHasErrors('email');
    }

    public function test_pharmacist_records_a_checkup(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);

        $this->actingAs($pharmacist)->post("/pharmacist/patients/{$patient->id}/checkup", [
            'checkup_date' => today()->toDateString(), 'blood_pressure' => '128/82', 'heart_rate' => 74, 'blood_sugar' => 6.4,
        ])->assertRedirect("/pharmacist/patients/{$patient->id}")->assertSessionHasNoErrors();

        $this->assertSame('128/82', HealthCheckup::where('patient_id', $patient->id)->value('blood_pressure'));
    }

    public function test_archived_patient_is_hidden_cannot_log_in_and_can_be_restored(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);

        $this->actingAs($pharmacist)->patch("/pharmacist/patients/{$patient->id}/archive")->assertRedirect();
        $this->assertNotNull($patient->fresh()->archived_at);
        $this->actingAs($pharmacist)->get('/pharmacist/patients')->assertDontSee($patient->user->email);

        $this->post('/logout');
        $this->post('/login', ['email' => $patient->user->email, 'password' => 'password']);
        $this->assertGuest();

        $this->actingAs($pharmacist)->patch("/pharmacist/patients/{$patient->id}/restore")->assertRedirect();
        $this->assertNull($patient->fresh()->archived_at);
        $this->assertNull($patient->user->fresh()->deactivated_at);
    }

    public function test_admin_adds_deactivates_and_reactivates_a_pharmacist(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/pharmacists', [
            'name' => 'New Pharmacist', 'email' => 'new.pharmacist@example.test',
            'phone_number' => '0123456789', 'password' => 'TempPass123!',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $staff = User::where('email', 'new.pharmacist@example.test')->firstOrFail();
        $this->assertSame('pharmacist', $staff->role);
        $this->assertTrue((bool) $staff->requires_password_change);

        $this->actingAs($admin)->patch("/admin/pharmacists/{$staff->id}/deactivate")->assertRedirect();
        $this->assertNotNull($staff->fresh()->deactivated_at);

        $this->actingAs($admin)->patch("/admin/pharmacists/{$staff->id}/reactivate")->assertRedirect();
        $this->assertNull($staff->fresh()->deactivated_at);
    }

    public function test_admin_moves_a_patient_to_another_pharmacist(): void
    {
        $patient = $this->patientOf($this->pharmacist());
        $newPharmacist = $this->pharmacist();

        $this->actingAs($this->admin())->patch("/admin/patients/{$patient->id}/pharmacist", [
            'pharmacist_id' => $newPharmacist->id,
        ])->assertRedirect();

        $this->assertSame($newPharmacist->id, $patient->fresh()->pharmacist_id);
    }
}
