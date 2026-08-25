<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which questions are worth putting on the homepage.
 *
 * The homepage showed every published question, so growing the FAQ made the
 * front page longer rather than the FAQ page fuller — and the answers somebody
 * needs before deciding to enquire are not the same ones they look up
 * afterwards.
 *
 * Existing questions keep their place: they are all on the homepage today, and
 * a migration that silently emptied that section would be a change nobody asked
 * for. New ones start off the homepage, which is the sensible default once the
 * list is long.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->boolean('show_on_homepage')->default(false)->after('is_published');
        });

        // Nothing disappears from the front page on the day this ships.
        DB::table('faqs')->update(['show_on_homepage' => true]);
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('show_on_homepage');
        });
    }
};
