<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sign-in for each role, and who may open which pages. */
class AuthAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_lands_on_its_own_dashboard_after_login(): void
    {
        $pharmacist = $this->pharmacist();
        $cases = [
            [$this->admin(), '/admin/dashboard'],
            [$pharmacist, '/pharmacist/dashboard'],
            [$this->patientOf($pharmacist)->user, '/patient/dashboard'],
        ];

        foreach ($cases as [$user, $dashboard]) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect();
            $this->assertAuthenticatedAs($user);
            $this->get('/dashboard')->assertRedirect($dashboard);
            $this->get($dashboard)->assertOk();
            $this->post('/logout');
        }
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->pharmacist();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_staff_cannot_sign_in(): void
    {
        $user = $this->pharmacist(['deactivated_at' => now()]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_user_who_must_change_password_is_sent_to_the_change_page(): void
    {
        $user = $this->pharmacist(['requires_password_change' => true]);

        $this->actingAs($user)->get('/pharmacist/dashboard')
            ->assertRedirect(route('password.force-change'));
    }

    public function test_roles_cannot_open_each_others_pages(): void
    {
        $pharmacist = $this->pharmacist();
        $patientUser = $this->patientOf($pharmacist)->user;

        $this->actingAs($patientUser)->get('/pharmacist/dashboard')->assertForbidden();
        $this->actingAs($patientUser)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($pharmacist)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($pharmacist)->get('/patient/dashboard')->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/admin/dashboard', '/pharmacist/dashboard', '/patient/dashboard', '/profile'] as $page) {
            $this->get($page)->assertRedirect('/login');
        }
    }

    public function test_pharmacist_cannot_open_another_pharmacists_patient(): void
    {
        $patient = $this->patientOf($this->pharmacist());

        $this->actingAs($this->pharmacist())
            ->get("/pharmacist/patients/{$patient->id}")
            ->assertNotFound();
    }
}
