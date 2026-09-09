<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes the wording for a button the first step no longer has.
 *
 * Choosing an assessment is now two cards that are pressed directly, so there is
 * nothing left to confirm and no confirm button to label. Left in the table the
 * field would sit in the admin panel offering to change a word that appears
 * nowhere — which is how an operator comes to spend an afternoon editing text
 * nobody will ever read.
 */
return new class extends Migration
{
    private const RETIRED = [
        ['startseite', 'hero', 'cta_button'],
        ['anfrage', 'formular', 'weiter'],
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
