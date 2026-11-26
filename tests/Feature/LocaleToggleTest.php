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
            ->assertSee('Mula Imbas Wajah')
            ->assertSee('WhatsApp +')
            ->assertDontSee('Start Face Verification');

        $this->get('/lang/en');
        $this->get('/')->assertSee('Start Face Verification');
    }

    public function test_kiosk_check_in_switches_back_to_english_for_the_next_person(): void
    {
        $face = array_fill(0, 128, 0.1);
        $this->patientOf($this->pharmacist(), ['face_descriptor' => json_encode($face), 'face_consent_at' => now()]);

        $this->get('/lang/ms');
        $this->get('/')->assertSee('Mula Imbas Wajah');

        $this->postJson(route('kiosk.identify'), ['descriptors' => [$face, $face, $face]])
            ->assertOk()
            ->assertJson(['matched' => true]);

        $this->get('/')->assertSee('<html lang="en">', false)->assertSee('Start Face Verification');
    }

    public function test_login_page_follows_the_language_choice(): void
    {
        $this->get('/login')->assertOk()->assertSee('Secure Login')->assertSee('Back to Kiosk');

        $this->get('/lang/ms');

        $this->get('/login')
            ->assertOk()
            ->assertSee('<html lang="ms">', false)
            ->assertSee('Lupa Kata Laluan?')
            ->assertSee('Kembali ke Kiosk')
            ->assertSee('Ingat Saya')
            ->assertDontSee('Secure Login');
    }

    public function test_login_errors_are_in_malay_when_bm_is_chosen(): void
    {
        $this->get('/lang/ms');

        $this->from('/login')->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'Sila masukkan e-mel.']);

        $user = \App\Models\User::factory()->create(['role' => 'patient']);
        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'E-mel atau kata laluan tidak sah.']);
    }

    public function test_unknown_language_is_rejected(): void
    {
        $this->get('/lang/fr')->assertNotFound();
    }
}
