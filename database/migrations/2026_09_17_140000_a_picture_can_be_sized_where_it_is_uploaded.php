<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The size of a service's or a city's own picture, in percent of the standard.
 *
 * Every picture beside a headline answered to the homepage's size, so one
 * portrait that needed to be a little smaller made every page smaller with it.
 * The size now sits next to the upload it belongs to. Empty means the default
 * for that kind of page, as it did before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->unsignedTinyInteger('image_size')->nullable()->after('image_path');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->unsignedTinyInteger('image_size')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('image_size');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('image_size');
        });
    }
};
