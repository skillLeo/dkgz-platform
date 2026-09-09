<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moves the footer wording from the homepage to the shell.
 *
 * Both footer texts were stored under `startseite`, but the footer stands on
 * every page. Each page hands its own block set to Inertia, so only the
 * homepage ever had these two — everywhere else the template fell back to the
 * wording compiled into it, and an operator who edited the footer saw it change
 * on the homepage and nowhere else. Exactly the fault the announcement bar had.
 *
 * The rows are moved rather than re-seeded so whatever the operator has already
 * written survives; re-seeding would have left their text orphaned under a page
 * key nothing reads any more.
 */
return new class extends Migration
{
    private const FIELDS = ['beschreibung', 'rechtshinweis'];

    public function up(): void
    {
        $this->move('startseite', 'layout');
    }

    public function down(): void
    {
        $this->move('layout', 'startseite');
    }

    private function move(string $from, string $to): void
    {
        foreach (self::FIELDS as $field) {
            $source = DB::table('content_blocks')
                ->where(['page_key' => $from, 'section_key' => 'fuss', 'field_key' => $field])
                ->first();

            if ($source === null) {
                continue;
            }

            // The destination can already exist if the seeder ran first. Its
            // value is the untouched default, so the operator's text wins and
            // the placeholder is the one that goes.
            DB::table('content_blocks')
                ->where(['page_key' => $to, 'section_key' => 'fuss', 'field_key' => $field])
                ->delete();

            DB::table('content_blocks')->where('id', $source->id)->update(['page_key' => $to]);
        }
    }
};
