<?php

namespace App\Console\Commands;

use App\Support\Audit;
use App\Support\DatabaseBackup;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Back up the whole database to storage/app/backups (keeps the newest ' . DatabaseBackup::KEEP . ')';

    public function handle(): int
    {
        $path = DatabaseBackup::create();
        $size = round(filesize($path) / 1024, 1);

        Audit::record('backup.created', 'Daily database backup ' . basename($path) . " ({$size} KB)");
        $this->info('Backup written: ' . basename($path) . " ({$size} KB)");

        return self::SUCCESS;
    }
}
