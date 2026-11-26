<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\HealthCheckup;
use App\Support\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        // Never touch the real storage/app/backups.
        $this->storage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pharmatrack-test-storage-' . uniqid();
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    private function readBackup(string $path): string
    {
        return str_ends_with($path, '.gz') ? gzdecode(file_get_contents($path)) : file_get_contents($path);
    }

    public function test_backup_contains_every_table_and_the_rows(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);
        HealthCheckup::create(['patient_id' => $patient->id, 'pharmacist_id' => $pharmacist->id,
            'checkup_date' => today(), 'blood_pressure' => "128/82", 'notes' => "Patient's note with 'quotes' and ; semicolon"]);

        $sql = $this->readBackup(DatabaseBackup::create());

        foreach (['users', 'patients', 'health_checkups', 'medications', 'medication_doses', 'audit_logs', 'migrations'] as $table) {
            $this->assertStringContainsString("CREATE TABLE `{$table}`", $sql);
        }
        $this->assertStringContainsString($pharmacist->email, $sql);
        $this->assertStringContainsString('128/82', $sql);
    }

    public function test_backup_can_be_restored(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = $this->patientOf($pharmacist);
        HealthCheckup::create(['patient_id' => $patient->id, 'pharmacist_id' => $pharmacist->id,
            'checkup_date' => today(), 'blood_pressure' => '128/82', 'notes' => "It's a \"tricky\" note; with \\ backslash"]);

        $sql = $this->readBackup(DatabaseBackup::create());

        DB::table('health_checkups')->delete();
        $this->assertSame(0, DB::table('health_checkups')->count());

        DB::unprepared($sql);   // what importing the file in SIDU does

        $restored = DB::table('health_checkups')->first();
        $this->assertSame('128/82', $restored->blood_pressure);
        $this->assertSame("It's a \"tricky\" note; with \\ backslash", $restored->notes);
        $this->assertSame($pharmacist->email, DB::table('users')->where('id', $pharmacist->id)->value('email'));
    }

    public function test_only_the_newest_backups_are_kept(): void
    {
        File::ensureDirectoryExists(DatabaseBackup::directory());
        for ($i = 1; $i <= DatabaseBackup::KEEP + 2; $i++) {
            touch(DatabaseBackup::directory() . sprintf('/pharmatrack-202601%02d-080000.sql.gz', $i));
        }

        DatabaseBackup::create();

        $names = array_column(DatabaseBackup::all(), 'name');
        $this->assertCount(DatabaseBackup::KEEP, $names);
        $this->assertStringStartsWith('pharmatrack-' . now()->format('Ymd'), $names[0], 'newest backup kept');
        $this->assertNotContains('pharmatrack-20260101-080000.sql.gz', $names, 'oldest backup removed');
    }

    public function test_daily_command_creates_a_backup_and_audits_it(): void
    {
        $this->artisan('backup:database')->assertSuccessful();

        $this->assertCount(1, DatabaseBackup::all());
        // Shown in Malaysia time, not UTC (was 8 hours off).
        $this->assertSame(now()->format('Y-m-d H'), DatabaseBackup::all()[0]['created_at']->format('Y-m-d H'));
        $this->assertTrue(AuditLog::where('action', 'backup.created')->exists());
        $this->assertContains('backup:database', \App\Support\DailyTasks::COMMANDS);
    }

    public function test_admin_can_back_up_and_download_with_audit(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/backups')->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success');
        $name = DatabaseBackup::all()[0]['name'];

        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee($name);
        $this->actingAs($admin)->get("/admin/backups/{$name}")->assertOk()->assertDownload($name);
        $this->assertTrue(AuditLog::where('action', 'backup.downloaded')->exists());
    }

    public function test_only_admins_can_reach_backups(): void
    {
        DatabaseBackup::create();
        $name = DatabaseBackup::all()[0]['name'];
        $pharmacist = $this->pharmacist();

        $this->actingAs($pharmacist)->get("/admin/backups/{$name}")->assertForbidden();
        $this->actingAs($pharmacist)->post('/admin/backups')->assertForbidden();
        $this->actingAs($this->patientOf($pharmacist)->user)->get("/admin/backups/{$name}")->assertForbidden();
        $this->post('/logout');
        $this->get("/admin/backups/{$name}")->assertRedirect('/login');
    }

    public function test_download_cannot_reach_other_files(): void
    {
        $admin = $this->admin();

        foreach (['..%2F..%2F.env', '.env', 'laravel.log', 'pharmatrack-20260101-080000.sql.gz'] as $file) {
            $this->actingAs($admin)->get("/admin/backups/{$file}")->assertNotFound();
        }
    }
}
