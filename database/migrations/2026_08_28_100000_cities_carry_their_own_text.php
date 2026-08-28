<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Room for something true about each city.
 *
 * "Kfz-Gutachter Köln" is one of the most valuable searches DKGZ can win, and
 * the page answering it was a heading and a list of services — thin enough that
 * a search engine has no reason to prefer it over anybody else's. The shared
 * sections underneath carry the structure for every city at once; these two
 * columns are for the part that cannot be templated, which is the part that
 * earns the ranking.
 *
 * Both optional: fifteen cities with nothing written are still better served by
 * the shared copy than by an empty section.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            // The local passage — courts, workshops, motorways, whatever is
            // actually true of assessing a vehicle in this place.
            $table->longText('body')->nullable()->after('intro');

            // Questions people ask about this city in particular, over and
            // above the ones every city page carries.
            $table->json('faqs')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['body', 'faqs']);
        });
    }
};
