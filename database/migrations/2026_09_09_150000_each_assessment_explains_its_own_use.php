<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an assessment is *for*, as opposed to what it is.
 *
 * The description is written for the services page and the directory, where it
 * sits under a heading and says what the assessment covers. Behind the "i" on
 * the request form somebody is asking a different question — "is this the one I
 * need?" — and the honest answer is a sentence about when you would want it,
 * not a definition.
 *
 * One column rather than reusing the description, because the two audiences are
 * different and a single sentence cannot serve both. Empty falls back to the
 * description, so nothing is blank while the office writes them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->text('info_de')->nullable()->after('description_de');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('info_de');
        });
    }
};
