<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moves the question off the hero, and gives it back its words.
 *
 * The hero no longer asks "welche Gutachtenart" — it offers two choices and
 * hands everything else to the request page — so its wording has nothing left
 * to label.
 *
 * On the request page the same two fields survive as the heading of the step,
 * and they were found empty: they were cleared while they labelled a dropdown
 * nobody wanted to see, and a cleared block stays cleared, so the new heading
 * would have rendered as a blank line. Only ever refilled when empty, because a
 * seeded default must never overwrite something somebody wrote.
 */
return new class extends Migration
{
    private const RETIRED = [
        ['startseite', 'hero', 'frage_leistung'],
        ['startseite', 'hero', 'frage_hinweis'],
    ];

    private const REFILL = [
        'frage_leistung' => 'Welches Gutachten benötigen Sie?',
        'frage_hinweis' => 'Wählen Sie die passende Leistung aus. Über das i erfahren Sie, wofür ein Gutachten gedacht ist.',
    ];

    public function up(): void
    {
        foreach (self::RETIRED as [$page, $section, $field]) {
            DB::table('content_blocks')
                ->where(['page_key' => $page, 'section_key' => $section, 'field_key' => $field])
                ->delete();
        }

        foreach (self::REFILL as $field => $value) {
            DB::table('content_blocks')
                ->where(['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => $field])
                ->where(fn ($query) => $query->whereNull('value')->orWhere('value', ''))
                ->update(['value' => $value]);
        }
    }

    /** The seeder no longer lists the hero pair, so there is nothing to put back. */
    public function down(): void
    {
        //
    }
};
