<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** PDPA: a face is only stored with the patient's recorded consent, and the patient can withdraw it. */
class FaceConsentTest extends TestCase
{
    use RefreshDatabase;

    /** A valid 128-number face template (what face-api.js produces). */
    private function descriptor(): array
    {
        return array_map(fn ($i) => round(sin($i) / 10, 6), range(1, 128));
    }

    private function newPatientForm(array $extra = []): array
    {
        return ['name' => 'Face Patient', 'email' => 'face.patient@example.test', 'age' => 40,
            'gender' => 'Male', 'height' => 170, 'weight' => 70] + $extra;
    }

    public function test_registering_with_a_face_requires_consent(): void
    {
        Notification::fake();
        $pharmacist = $this->pharmacist();

        $this->actingAs($pharmacist)->post('/pharmacist/patients', $this->newPatientForm([
            'face_descriptor' => json_encode($this->descriptor()),
        ]))->assertSessionHasErrors('face_consent');

        $this->assertFalse(User::where('email', 'face.patient@example.test')->exists());
    }

    public function test_registering_with_a_face_and_consent_records_who_and_when(): void
    {
        Notification::fake();
        $pharmacist = $this->pharmacist();

        $this->actingAs($pharmacist)->post('/pharmacist/patients', $this->newPatientForm([
            'face_descriptor' => json_encode($this->descriptor()),
            'face_consent' => '1',
        ]))->assertSessionHasNoErrors();

        $patient = Patient::whereHas('user', fn ($q) => $q->where('email', 'face.patient@example.test'))->firstOrFail();
        $this->assertTrue($patient->hasFace());
        $this->assertNotNull($patient->face_consent_at);
        $this->assertSame($pharmacist->id, $patient->face_consent_by);
        $this->assertTrue(AuditLog::where('action', 'patient.face_consent_given')->where('patient_id', $patient->id)->exists());
    }

    public function test_registering_without_a_face_needs_no_consent(): void
    {
        Notification::fake();

        $this->actingAs($this->pharmacist())->post('/pharmacist/patients', $this->newPatientForm())
            ->assertSessionHasNoErrors();
    }

    public function test_real_form_with_empty_face_field_needs_no_consent(): void
    {
        Notification::fake();

        // The register page always sends the hidden face_descriptor input, empty when no face was captured.
        $this->actingAs($this->pharmacist())->post('/pharmacist/patients', $this->newPatientForm(['face_descriptor' => '']))
            ->assertSessionHasNoErrors();
        $this->assertTrue(User::where('email', 'face.patient@example.test')->exists());
    }

    public function test_updating_a_face_requires_consent(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);

        $this->actingAs($pharmacist)->postJson('/pharmacist/patients/update-face', [
            'patient_id' => $patient->id, 'descriptor' => $this->descriptor(),
        ])->assertStatus(422)->assertJsonValidationErrors('consent');
        $this->assertFalse($patient->fresh()->hasFace());

        $this->actingAs($pharmacist)->postJson('/pharmacist/patients/update-face', [
            'patient_id' => $patient->id, 'descriptor' => $this->descriptor(), 'consent' => true,
        ])->assertOk()->assertJson(['status' => 'success']);

        $patient->refresh();
        $this->assertTrue($patient->hasFace());
        $this->assertSame($pharmacist->id, $patient->face_consent_by);
    }

    public function test_older_face_without_consent_is_flagged_and_consent_can_be_recorded(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist, ['face_descriptor' => json_encode($this->descriptor())]);

        $this->assertTrue($patient->faceConsentMissing());
        $this->actingAs($pharmacist)->get("/pharmacist/patients/{$patient->id}")->assertOk()->assertSee('Consent not recorded');
        $this->actingAs($this->admin())->get('/admin/patients')->assertOk()->assertSee('No consent');

        $this->actingAs($pharmacist)->post("/pharmacist/patients/{$patient->id}/face-consent", [])
            ->assertSessionHasErrors('consent');
        $this->actingAs($pharmacist)->post("/pharmacist/patients/{$patient->id}/face-consent", ['consent' => '1'])
            ->assertRedirect();

        $this->assertFalse($patient->fresh()->faceConsentMissing());
        $this->actingAs($pharmacist)->get("/pharmacist/patients/{$patient->id}")->assertDontSee('Consent not recorded');
    }

    public function test_pharmacist_removing_a_face_clears_the_consent(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist, ['face_descriptor' => json_encode($this->descriptor())]);
        $patient->recordFaceConsent($pharmacist);

        $this->actingAs($pharmacist)->delete("/pharmacist/patients/{$patient->id}/face")->assertRedirect();

        $patient->refresh();
        $this->assertFalse($patient->hasFace());
        $this->assertNull($patient->face_consent_at);
        $this->assertTrue(AuditLog::where('action', 'patient.face_removed')->exists());
    }

    public function test_patient_sees_status_and_can_withdraw_which_deletes_the_face(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist, ['face_descriptor' => json_encode($this->descriptor())]);
        $patient->recordFaceConsent($pharmacist);

        $this->actingAs($patient->user)->get('/profile')->assertOk()
            ->assertSee('Face Recognition')->assertSee('Status: on.')->assertSee('Withdraw consent');

        $this->actingAs($patient->user)->delete('/patient/face')
            ->assertRedirect(route('profile.edit') . '#face-recognition');

        $patient->refresh();
        $this->assertFalse($patient->hasFace());
        $this->assertNull($patient->face_consent_at);
        $this->assertTrue(AuditLog::where('action', 'patient.face_consent_withdrawn')->where('patient_id', $patient->id)->exists());
        $this->actingAs($patient->user)->get('/profile')->assertSee('Status: off.');
    }

    public function test_withdrawn_patient_is_no_longer_matched_by_the_kiosk(): void
    {
        $pharmacist = $this->pharmacist();
        $face = $this->descriptor();
        $patient = $this->patientOf($pharmacist, ['face_descriptor' => json_encode($face)]);
        $patient->recordFaceConsent($pharmacist);

        $patient->removeFace();

        $this->assertSame(0, Patient::whereNotNull('face_descriptor')->where('face_descriptor', '!=', '')->count());
    }

    public function test_staff_cannot_use_the_patient_withdraw_route_and_patients_cannot_record_consent(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist, ['face_descriptor' => json_encode($this->descriptor())]);

        $this->actingAs($pharmacist)->delete('/patient/face')->assertForbidden();
        $this->actingAs($patient->user)->post("/pharmacist/patients/{$patient->id}/face-consent", ['consent' => '1'])
            ->assertForbidden();
        $this->assertTrue($patient->fresh()->faceConsentMissing());
    }
}
