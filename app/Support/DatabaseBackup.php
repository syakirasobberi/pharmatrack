<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;

/**
 * Plain-PHP database backup: every table's structure and rows as SQL, gzipped.
 *
 * The live host has no shell (no mysqldump) and no cron, so this runs from DailyTasks.
 * Files live in storage/app/backups, which the web server never serves directly;
 * admins download them through an audited admin route. Restore by importing the .sql
 * (after un-gzipping) in SIDU / phpMyAdmin.
 */
class DatabaseBackup
{
    /** How many daily backups to keep on the server. */
    public const KEEP = 7;

    private const ROWS_PER_INSERT = 100;

    /** Only our own file names: pharmatrack-YYYYMMDD-HHMMSS.sql(.gz) */
    public const FILE_PATTERN = '/^pharmatrack-\d{8}-\d{6}\.sql(\.gz)?$/';

    public static function directory(): string
    {
        return storage_path('app/backups');
    }

    /** Writes a new backup, removes old ones beyond KEEP, and returns the new file's full path. */
    public static function create(): string
    {
        File::ensureDirectoryExists(self::directory());

        $gzip = function_exists('gzopen');
        $name = 'pharmatrack-' . now()->format('Ymd-His') . ($gzip ? '.sql.gz' : '.sql');
        $path = self::directory() . DIRECTORY_SEPARATOR . $name;
        $partial = $path . '.part';   // never leave a half-written file that looks complete

        $out = $gzip ? gzopen($partial, 'wb6') : fopen($partial, 'wb');
        $write = $gzip ? fn (string $s) => gzwrite($out, $s) : fn (string $s) => fwrite($out, $s);

        try {
            self::dump(DB::connection()->getPdo(), $write);
        } finally {
            $gzip ? gzclose($out) : fclose($out);
        }

        rename($partial, $path);
        self::prune();

        return $path;
    }

    /** Newest first: [['name' => ..., 'size' => bytes, 'created_at' => Carbon], ...] */
    public static function all(): array
    {
        if (! is_dir(self::directory())) {
            return [];
        }

        return collect(File::files(self::directory()))
            ->filter(fn ($file) => preg_match(self::FILE_PATTERN, $file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getFilename())   // names sort by date
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => \Carbon\Carbon::createFromTimestamp($file->getMTime(), config('app.timezone')),
            ])
            ->values()
            ->all();
    }

    /** Full path of one of our backups, or null (blocks path tricks like ../.env). */
    public static function path(string $name): ?string
    {
        if (! preg_match(self::FILE_PATTERN, $name)) {
            return null;
        }
        $path = self::directory() . DIRECTORY_SEPARATOR . $name;

        return is_file($path) ? $path : null;
    }

    private static function prune(): void
    {
        foreach (array_slice(self::all(), self::KEEP) as $old) {
            @unlink(self::directory() . DIRECTORY_SEPARATOR . $old['name']);
        }
    }

    private static function dump(PDO $pdo, callable $write): void
    {
        $database = $pdo->query('SELECT DATABASE()')->fetchColumn();

        $write("-- PharmaTrack database backup\n-- Database: {$database}\n-- Created: " . now()->toDateTimeString() . "\n\n");
        $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $quoted = '`' . str_replace('`', '``', $table) . '`';
            $create = $pdo->query("SHOW CREATE TABLE {$quoted}")->fetch(PDO::FETCH_NUM)[1];

            $write("-- Table {$table}\nDROP TABLE IF EXISTS {$quoted};\n{$create};\n\n");

            $rows = $pdo->query("SELECT * FROM {$quoted}", PDO::FETCH_ASSOC);
            $batch = [];
            $columns = null;
            foreach ($rows as $row) {
                $columns ??= '(' . implode(', ', array_map(fn ($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row))) . ')';
                $batch[] = '(' . implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';

                if (count($batch) === self::ROWS_PER_INSERT) {
                    $write("INSERT INTO {$quoted} {$columns} VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                $write("INSERT INTO {$quoted} {$columns} VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            $write("\n");
        }

        $write("SET FOREIGN_KEY_CHECKS=1;\n-- End of backup\n");
    }
}
