<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The assessor's portrait, shown to the customer alongside the contact details
 * they receive on acceptance. A name and a phone number is a stranger; a face
 * is a person about to inspect their car.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessors', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('assessors', fn (Blueprint $table) => $table->dropColumn('photo_path'));
    }
};
