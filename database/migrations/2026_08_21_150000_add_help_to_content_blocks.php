<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The editing hint the admin panel already tried to show.
 *
 * The seeder carried help text for the awkward blocks — which aspect ratio a
 * photograph wants, what a placeholder is standing in for — the editor read it,
 * and there was no column behind it, so every field appeared without guidance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->string('help_de', 300)->nullable()->after('label_de');
        });
    }

    public function down(): void
    {
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->dropColumn('help_de');
        });
    }
};
