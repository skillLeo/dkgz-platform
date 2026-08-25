<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questions belonging to one particular kind of assessment.
 *
 * The site already has a general FAQ, which answers "what is DKGZ" and "does it
 * cost anything". A service page needs the other sort — what a Wertgutachten
 * actually contains, how long an Oldtimergutachten takes — and those questions
 * belong to the service rather than to the site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->json('faqs')->nullable()->after('additional_info_de');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('faqs');
        });
    }
};
