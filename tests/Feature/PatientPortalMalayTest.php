<?php

namespace Tests\Feature;

use App\Models\HealthCheckup;
use App\Models\Medication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPortalMalayTest extends TestCase
{
    use RefreshDatabase;

    private function malayPatient(): array
    {
        $patient = $this->patientOf($this->pharmacist());
        $medication = Medication::create([
            'patient_id' => $patient->id,
            'name' => 'Metformin',
            'dosage' => '500mg',
            'frequency' => 'Twice daily',
            'start_date' => now()->subDays(10),
        ]);
        HealthCheckup::create([
            'patient_id' => $patient->id,
            'checkup_date' => now()->subDays(3),
            'blood_pressure' => '140/90',
            'blood_sugar' => 5.0,
        ]);

        $this->actingAs($patient->user)->get('/lang/ms');

        return [$patient, $medication];
    }

    public function test_patient_portal_pages_are_in_malay_when_bm_is_chosen(): void
    {
        $this->malayPatient();

        $this->get(route('patient.dashboard'))->assertOk()
            ->assertSee('<html lang="ms">', false)
            ->assertSee('Kesihatan Saya')
            ->assertSee('Ubat Semasa')
            ->assertSee('Log Keluar')
            ->assertSee('Hubungi Kami')
            ->assertSee('Tekanan Darah')
            ->assertSee('Tinggi')               // BP 140/90 is flagged high
            ->assertDontSee('Current Medications');

        $this->get(route('patient.medications'))->assertOk()
            ->assertSee('Saya dah ambil hari ini')
            ->assertSee('Mohon ubat susulan')
            ->assertDontSee('I took it today');

        $this->get(route('patient.checkups'))->assertOk()
            ->assertSee('Pemeriksaan Saya')
            ->assertSee('Gula Darah Berpuasa')
            ->assertDontSee('Fasting Blood Sugar');

        $this->get(route('patient.notifications.index'))->assertOk()
            ->assertSee('Peti Notifikasi')
            ->assertSee('Tiada Notifikasi');
    }

    public function test_patient_actions_reply_in_malay(): void
    {
        [, $medication] = $this->malayPatient();

        $this->post(route('patient.medications.doses.store', $medication))
            ->assertSessionHas('success', 'Direkod: anda telah ambil Metformin hari ini.');

        $this->from(route('patient.medications'))->post(route('patient.medications.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Sila masukkan nama ubat.']);
    }

    public function test_portal_stays_english_by_default_and_for_staff(): void
    {
        $patient = $this->patientOf($this->pharmacist());
        $this->actingAs($patient->user)->get(route('patient.dashboard'))
            ->assertOk()->assertSee('Current Medications')->assertSee('Log Out');

        // A pharmacist who somehow has BM set still gets a working page.
        $pharmacist = $this->pharmacist();
        $this->actingAs($pharmacist)->withSession(['locale' => 'ms', 'two_factor_passed' => true]);
        $this->get(route('pharmacist.patients.index'))->assertStatus(200);
    }

    public function test_reset_and_first_login_password_pages_in_malay(): void
    {
        $this->get('/lang/ms');

        $this->get(route('password.reset', ['token' => 'abc', 'email' => 'someone@example.com']))
            ->assertOk()
            ->assertSee('Tetapkan kata laluan baharu')
            ->assertSee('Simpan Kata Laluan')
            ->assertDontSee('Reset Password');

        $user = $this->patientOf($this->pharmacist())->user;
        $user->forceFill(['requires_password_change' => true])->save();
        $this->actingAs($user)->get(route('password.force-change'))
            ->assertOk()
            ->assertSee('Ini kali pertama anda log masuk')
            ->assertSee('Simpan &amp; Teruskan', false);
    }
}
