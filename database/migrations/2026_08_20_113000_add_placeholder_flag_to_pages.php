<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marks legal pages still carrying seeded placeholder text.
 *
 * Impressum, Datenschutz, AGB and Widerruf carry legal obligations in Germany.
 * Shipping the seeded drafts as if they were reviewed text is the kind of thing
 * that is only noticed when it matters, so the editor says so until a person
 * has actually replaced them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_placeholder')->default(true)->after('is_published');
        });

        DB::table('pages')->update(['is_placeholder' => true]);
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('is_placeholder'));
    }
};
