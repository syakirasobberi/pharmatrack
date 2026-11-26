<?php

namespace Tests\Feature;

use App\Models\HealthCheckup;
use App\Models\MedicalHistory;
use App\Models\Medication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacistMalayTest extends TestCase
{
    use RefreshDatabase;

    /** A pharmacist with one patient who has history, two check-ups, meds (one patient-added, one ending, one stopped). */
    private function setUpClinic(): array
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist, ['face_descriptor' => json_encode(array_fill(0, 128, 0.1))]);
        MedicalHistory::create(['patient_id' => $patient->id, 'hypertension' => 'Monitored', 'diabetes' => 'Type 2 (Controlled)',
            'allergies' => 'Dust', 'drug_allergies' => 'Penicillin', 'others' => 'Asthma']);
        HealthCheckup::create(['patient_id' => $patient->id, 'checkup_date' => now()->subDays(40), 'blood_pressure' => '130/85', 'blood_sugar' => 6.5, 'cholesterol' => 5.0]);
        $checkup = HealthCheckup::create(['patient_id' => $patient->id, 'checkup_date' => now()->subDays(2), 'blood_pressure' => '150/95', 'blood_sugar' => 7.8, 'cholesterol' => 6.4]);
        $med = Medication::create(['patient_id' => $patient->id, 'name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'Twice daily',
            'start_date' => now()->subDays(20), 'end_date' => now()->addDays(5), 'supply_until' => now()->addDays(2)]);
        $med->forceFill(['refill_requested_at' => now()->subDay()])->save();
        Medication::create(['patient_id' => $patient->id, 'name' => 'Amlodipine', 'dosage' => '5mg', 'frequency' => 'Once daily', 'start_date' => now()->subDays(60)]);
        $stopped = Medication::create(['patient_id' => $patient->id, 'name' => 'Aspirin', 'dosage' => '100mg', 'frequency' => 'Once daily', 'start_date' => now()->subDays(90)]);
        $stopped->forceFill(['stopped_at' => now()->subDays(10), 'stopped_reason' => 'Side effects'])->save();
        $med->recordDose(now()->subDay(), 'pharmacist', $pharmacist);

        return [$pharmacist, $patient, $med, $checkup];
    }

    private function pages($patient, $med, $checkup): array
    {
        return [
            'dashboard' => route('pharmacist.dashboard'),
            'patients' => route('pharmacist.patients.index'),
            'register' => route('pharmacist.patients.create'),
            'edit' => route('pharmacist.patients.edit', $patient->id),
            'show' => route('pharmacist.patients.show', $patient->id),
            'summary' => route('pharmacist.patients.summary', $patient->id),
            'medical' => route('pharmacist.patients.medical.edit', $patient->id),
            'checkup' => route('pharmacist.checkups.create', $patient->id),
            'checkup-edit' => route('pharmacist.checkups.edit', $checkup->id),
            'medications' => route('pharmacist.medication.index', $patient->id),
            'medication-edit' => route('pharmacist.medication.edit', $med->id),
            'quick-scan' => route('pharmacist.quickScan'),
            'profile' => route('profile.edit'),
        ];
    }

    public function test_every_pharmacist_page_opens_in_english_and_malay(): void
    {
        [$pharmacist, $patient, $med, $checkup] = $this->setUpClinic();
        $this->actingAs($pharmacist);

        foreach ($this->pages($patient, $med, $checkup) as $name => $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/lang/ms');
        $dump = getenv('MS_TEXT_DUMP');
        foreach ($this->pages($patient, $med, $checkup) as $name => $url) {
            $html = $this->get($url)->assertOk()->assertSee('<html lang="ms">', false)->getContent();
            if ($dump) {
                // Visible text only, for the leftover-English audit.
                $text = preg_replace(['#<script\b.*?</script>#is', '#<style\b.*?</style>#is'], '', $html);
                $text = html_entity_decode(strip_tags(preg_replace('/<(br|p|div|li|tr|h\d|span|td|th|option|button|a|label)\b/i', "\n<$1", $text)));
                file_put_contents("$dump/$name.txt", implode("\n", array_filter(array_map('trim', explode("\n", $text)))));
            }
        }
    }

    public function test_key_pharmacist_screens_show_malay(): void
    {
        [$pharmacist, $patient, $med, $checkup] = $this->setUpClinic();
        $this->actingAs($pharmacist)->get('/lang/ms');

        $this->get(route('pharmacist.dashboard'))->assertSee('Amaran Klinikal')->assertSee('Papan Pemuka')->assertDontSeeText('Clinical Alerts');
        $this->get(route('pharmacist.patients.show', $patient->id))->assertSee('Sejarah Pemeriksaan Kesihatan')->assertSee('Kencing Manis');
        $this->get(route('pharmacist.medication.index', $patient->id))->assertSee('Senarai Ubat')->assertSee('Ubat Terdahulu (1)');
        $this->get(route('pharmacist.patients.summary', $patient->id))->assertSee('Risiko Kesihatan')->assertSee('Perlu pemantauan rapi');

        // Flash message and validation in Malay.
        $this->post(route('pharmacist.medication.review', $med->id))->assertSessionHas('success', 'Metformin ditanda sudah disemak.');
        $this->from(route('pharmacist.patients.create'))->post(route('pharmacist.patients.store'), [])
            ->assertSessionHasErrors(['name' => 'Sila masukkan nama.']);
    }

    public function test_pdf_summary_stays_english_for_a_malay_pharmacist(): void
    {
        [$pharmacist, $patient] = $this->setUpClinic();
        $this->actingAs($pharmacist)->get('/lang/ms');

        $pdf = $this->get(route('pharmacist.patients.summary.download', $patient->id))->assertOk()->getContent();
        $this->assertStringContainsString('Overall Summary', $pdf);
    }
}
