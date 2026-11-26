<?php

namespace Tests;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /** Tests wipe the database they run on, so they may only ever touch this one. */
    private const TEST_DATABASE = 'pharmatrack_test';

    /** Runs before RefreshDatabase wipes anything (setUpTraits is called inside parent::setUp). */
    protected function setUpTraits()
    {
        $database = DB::connection()->getDatabaseName();
        if ($database !== self::TEST_DATABASE) {
            // e.g. a cached config (php artisan config:cache) ignores phpunit.xml and points at the real DB.
            throw new \RuntimeException(
                "Refusing to run tests on database \"{$database}\". Expected \"" . self::TEST_DATABASE . '". '
                . 'Run "php artisan config:clear" and check phpunit.xml.'
            );
        }

        return parent::setUpTraits();
    }

    protected function admin(array $attributes = []): User
    {
        return User::factory()->create(['role' => 'admin'] + $attributes);
    }

    protected function pharmacist(array $attributes = []): User
    {
        return User::factory()->create(['role' => 'pharmacist'] + $attributes);
    }

    /** A patient (with login) assigned to $pharmacist. */
    protected function patientOf(User $pharmacist, array $attributes = []): Patient
    {
        $user = User::factory()->create(['role' => 'patient']);

        return Patient::create($attributes + [
            'user_id' => $user->id,
            'pharmacist_id' => $pharmacist->id,
            'age' => 45,
            'gender' => 'Female',
            'height' => 160,
            'weight' => 62,
            'bmi' => 24.2,
        ]);
    }
}
