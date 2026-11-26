<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_is_english_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Start Face Verification');
    }

    public function test_bm_toggle_switches_welcome_page_to_malay_and_back(): void
    {
        $this->from('/')->get('/lang/ms')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="ms">', false)
            ->assertSee('Mula Pengesahan Wajah')
            ->assertSee('Sembang di +')
            ->assertDontSee('Start Face Verification');

        $this->get('/lang/en');
        $this->get('/')->assertSee('Start Face Verification');
    }

    public function test_kiosk_check_in_switches_back_to_english_for_the_next_person(): void
    {
        $face = array_fill(0, 128, 0.1);
        $this->patientOf($this->pharmacist(), ['face_descriptor' => json_encode($face), 'face_consent_at' => now()]);

        $this->get('/lang/ms');
        $this->get('/')->assertSee('Mula Pengesahan Wajah');

        $this->postJson(route('kiosk.identify'), ['descriptors' => [$face, $face, $face]])
            ->assertOk()
            ->assertJson(['matched' => true]);

        $this->get('/')->assertSee('<html lang="en">', false)->assertSee('Start Face Verification');
    }

    public function test_unknown_language_is_rejected(): void
    {
        $this->get('/lang/fr')->assertNotFound();
    }
}
