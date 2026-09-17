<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A picture beside the headline of a service page and a city page.
 *
 * The homepage had one and these pages had an empty half next to their
 * headline. One column on each, holding a path on the public disk the same way
 * a content block does, and empty until somebody uploads something: an empty
 * one falls back to the default picture for that kind of page, and from there
 * to the homepage's, so no page is ever left without one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('info_de');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
