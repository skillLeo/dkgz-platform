<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes the wording for a button the first step no longer has.
 *
 * The remaining assessments were a dropdown with a button under it. They are now
 * the same pressable rows as the two that open the step, so there is nothing
 * left to confirm and nothing to label. Left in the table the field would sit in
 * the admin panel offering to change a word that appears nowhere.
 *
 * This is the second time this field has gone. It came back when the dropdown
 * came back, and an operator's own wording went with it that time — so if a
 * confirm button is ever wanted again, seed the field again rather than assuming
 * the old value is still somewhere.
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
