<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One format for the day a funnel row belongs to.
 *
 * Two write paths disagreed: record() wrote "2026-08-28" and anything saved
 * through the model wrote "2026-08-28 00:00:00". The column is a date, so on
 * MySQL the engine flattened both — but on SQLite the funnel query compared
 * strings and a row with a time component fell outside every range that
 * included its own day. Either way the table should hold one shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('funnel_events')->select('id', 'day')->get() as $row) {
            $day = substr((string) $row->day, 0, 10);

            if ($day !== $row->day) {
                DB::table('funnel_events')->where('id', $row->id)->update(['day' => $day]);
            }
        }
    }

    /** Nothing to undo: the short form is the column's own format. */
    public function down(): void
    {
        //
    }
};
