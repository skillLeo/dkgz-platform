<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Every German postal code, so the form can name the town back to the visitor.
 *
 * The request form now asks for a postal code and nothing else about where the
 * car is: it looks the town up and shows it, which is one field fewer to type
 * and removes the mismatch between a code and a town somebody typed by hand.
 * That only works if the table is complete — the old one held 179 codes, so
 * almost every real visitor would have been told their postal code could not be
 * found, on the one screen that decides whether they enquire at all.
 *
 * Codes issued to a single large recipient rather than an area — a bank, an
 * insurer, a Postfach — resolve to the town they sit in rather than to the
 * company's name, because "40476 Deutsche Post AG" is not an answer to "where
 * is your car".
 *
 * Data: GeoNames postal codes for Germany, CC BY 4.0 (https://www.geonames.org).
 * Stored gzipped because 10 812 rows of PHP array would be a megabyte of source
 * nobody will ever read, and read in chunks so the seeder stays well inside a
 * shared host's memory limit.
 */
class PostalCodeSeeder extends Seeder
{
    private const SOURCE = 'postal-codes-de.csv.gz';

    private const CHUNK = 500;

    public function run(): void
    {
        $path = database_path('data/'.self::SOURCE);

        if (! is_file($path)) {
            throw new RuntimeException("Die PLZ-Datei fehlt: {$path}");
        }

        $handle = gzopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Die PLZ-Datei konnte nicht gelesen werden: {$path}");
        }

        $now = now();
        $batch = [];
        $written = 0;

        while (($row = fgetcsv($handle, 512, ',', '"', '\\')) !== false) {
            if (count($row) < 3 || $row[0] === '') {
                continue;
            }

            $batch[] = [
                'code' => $row[0],
                'city' => $row[1],
                'state' => $row[2],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= self::CHUNK) {
                $written += $this->write($batch);
                $batch = [];
            }
        }

        gzclose($handle);

        if ($batch !== []) {
            $written += $this->write($batch);
        }

        $this->command?->info("{$written} Postleitzahlen eingelesen.");
    }

    /**
     * Upsert rather than insert: reseeding must not collide with what is there,
     * and a town that has been renamed should end up with its new name.
     *
     * @param  list<array<string, mixed>>  $batch
     */
    private function write(array $batch): int
    {
        DB::table('postal_codes')->upsert($batch, ['code'], ['city', 'state', 'updated_at']);

        return count($batch);
    }
}
