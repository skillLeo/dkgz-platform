<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A postal code names one town, so the table should hold it once.
 *
 * Without the constraint the reference table can be seeded twice and start
 * answering "which town is 40210" with two rows, and the seeder cannot upsert —
 * it has nothing to match on. Duplicates are cleared out first, keeping the row
 * that was written first.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('postal_codes')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('count(*) > 1')
            ->pluck('code');

        foreach ($duplicates as $code) {
            $keep = DB::table('postal_codes')->where('code', $code)->min('id');

            DB::table('postal_codes')->where('code', $code)->where('id', '!=', $keep)->delete();
        }

        Schema::table('postal_codes', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('postal_codes', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }
};
