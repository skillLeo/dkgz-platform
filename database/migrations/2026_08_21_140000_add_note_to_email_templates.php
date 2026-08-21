<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The closing note under the separator line becomes part of the template.
 *
 * It was passed in from whichever job sent the message, so the one piece of
 * legal-sounding text at the bottom of a mail — "DKGZ hat ausschließlich
 * vermittelt" and its like — was the only part an operator could not change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->text('note_de')->nullable()->after('body_html');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('note_de');
        });
    }
};
