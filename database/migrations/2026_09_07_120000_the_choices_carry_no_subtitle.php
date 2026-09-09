<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes the subtitle under each of the two choices.
 *
 * The first step now shows a mark and a name and nothing else — two choices with
 * a sentence under each read as a paragraph to be worked through, in a hero that
 * already has a headline, a strapline and a row of faces asking for the same
 * glance. The description still appears where it earns its place: on the step
 * after this one, under what was actually chosen.
 *
 * The button under the list came back with it, so its wording is seeded again
 * rather than removed here.
 */
return new class extends Migration
{
    private const RETIRED = [
        ['startseite', 'hero', 'option_weitere_text'],
        ['anfrage', 'formular', 'option_weitere_text'],
    ];

    public function up(): void
    {
        foreach (self::RETIRED as [$page, $section, $field]) {
            DB::table('content_blocks')
                ->where(['page_key' => $page, 'section_key' => $section, 'field_key' => $field])
                ->delete();
        }
    }

    /** The seeder no longer lists them, so there is nothing to put back. */
    public function down(): void
    {
        //
    }
};
