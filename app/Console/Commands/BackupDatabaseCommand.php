<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Writes a gzipped SQL dump to private storage and keeps the last fourteen.
 *
 * mysqldump is not available on this host and exec() is disabled, so the dump
 * is built through the existing PDO connection. That is slower than the shell
 * tool but it is the only route that works here, and a backup that cannot run
 * is not a backup.
 */
class BackupDatabaseCommand extends Command
{
    protected $signature = 'dkgz:backup-database {--keep=14 : Anzahl der aufzubewahrenden Sicherungen}';

    protected $description = 'Sichert die Datenbank als gzip-komprimierten SQL-Dump.';

    private const DISK = 'private';

    private const DIRECTORY = 'backups';

    public function handle(): int
    {
        $file = $this->databaseFile();
        $name = 'dkgz-'.now()->format('Y-m-d-His').($file !== null ? '.sqlite.gz' : '.sql.gz');
        $path = self::DIRECTORY.'/'.$name;

        try {
            // A file-backed SQLite database is its own dump, and copying it is
            // both faster and more faithful. Everything else — MySQL, and an
            // in-memory SQLite under test — is rebuilt from statements.
            $sql = $file !== null
                ? (string) file_get_contents($file)
                : $this->dump();
        } catch (Throwable $e) {
            $this->error('Sicherung fehlgeschlagen: '.$e->getMessage());

            return self::FAILURE;
        }

        Storage::disk(self::DISK)->put($path, gzencode($sql, 6));

        $size = Storage::disk(self::DISK)->size($path);
        $this->info("Sicherung geschrieben: {$name} (".number_format($size / 1000, 0, ',', '.').' KB)');

        $this->prune((int) $this->option('keep'));

        return self::SUCCESS;
    }

    /** The path of a file-backed SQLite database, or null for anything else. */
    private function databaseFile(): ?string
    {
        if (DB::getDriverName() !== 'sqlite') {
            return null;
        }

        $name = DB::connection()->getDatabaseName();

        return $name !== ':memory:' && is_file($name) ? $name : null;
    }

    private function dump(): string
    {
        $database = DB::connection()->getDatabaseName();

        $sql = "-- DKGZ Sicherung {$database} · ".now()->format('d.m.Y H:i')."\n"
            ."SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($this->tables() as $table) {
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n".$this->createStatement($table).";\n\n";

            // Chunked so a large table cannot exhaust the host's memory limit.
            DB::table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use (&$sql, $table) {
                foreach ($rows as $row) {
                    $values = collect((array) $row)
                        ->map(fn ($value) => match (true) {
                            $value === null => 'NULL',
                            is_int($value), is_float($value) => (string) $value,
                            default => DB::connection()->getPdo()->quote((string) $value),
                        })
                        ->implode(', ');

                    $sql .= "INSERT INTO `{$table}` VALUES ({$values});\n";
                }
            });

            $sql .= "\n";
        }

        return $sql."SET FOREIGN_KEY_CHECKS=1;\n";
    }

    private function createStatement(string $table): string
    {
        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table]);

            return (string) ($row->sql ?? '');
        }

        $row = DB::selectOne("SHOW CREATE TABLE `{$table}`");

        return (string) ($row->{'Create Table'} ?? '');
    }

    /** @return array<int, string> */
    private function tables(): array
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            ))->pluck('name')->all();
        }

        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->all();
    }

    private function prune(int $keep): void
    {
        $files = collect(Storage::disk(self::DISK)->files(self::DIRECTORY))
            ->filter(fn (string $file) => str_ends_with($file, '.gz'))
            ->sortDesc()
            ->values();

        $stale = $files->slice($keep);

        foreach ($stale as $file) {
            Storage::disk(self::DISK)->delete($file);
        }

        if ($stale->isNotEmpty()) {
            $this->line("{$stale->count()} ältere Sicherung(en) entfernt.");
        }
    }
}
