<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retires the first step's own headline and strapline.
 *
 * They repeated the homepage almost word for word, which told somebody who had
 * just arrived from there nothing at all. The step now leads with the question
 * it actually asks — "Welches Gutachten benötigen Sie?" — and that wording lives
 * where it belongs, under Anfrageformular, beside the list it heads.
 *
 * Left in the table these two would sit in the admin panel offering to change
 * text that appears nowhere.
 */
return new class extends Migration
{
    /**
     * Also the wording of things the step no longer has.
     *
     * The hero used to expand into a list with a way back out of it, and the
     * first step used to be a box with a heading of its own inside the page.
     * The hero now hands over to this page and this page is the list, so there
     * is no second screen to head and nothing to go back from.
     */
    private const RETIRED = [
        ['anfrage', 'kopf', 'ueberschrift'],
        ['anfrage', 'kopf', 'text'],
        ['anfrage', 'formular', 'cta_schritt_1'],
        ['anfrage', 'formular', 'option_weitere'],
        ['anfrage', 'formular', 'zurueck'],
        ['startseite', 'hero', 'zurueck'],
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
