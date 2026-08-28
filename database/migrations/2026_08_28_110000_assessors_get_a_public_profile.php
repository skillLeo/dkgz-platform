<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A page of their own for each partner.
 *
 * The directory is a real SEO asset — a hundred and thirty pages naming a firm,
 * a region and the assessments they do — and it lets somebody who has been
 * recommended a particular assessor reach them through DKGZ rather than around
 * it.
 *
 * Nothing that identifies a person beyond the trading name goes on it: no
 * telephone number, no e-mail address, no street. A partner who is listed can
 * be asked for work through the platform and reached no other way, which is the
 * point of the platform.
 *
 * is_listed defaults to true for the directory to have anybody in it on day
 * one, and both the office and the partner can switch it off. Partners signed
 * up before this existed and should be told it now does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessors', function (Blueprint $table) {
            // The address of the profile, built from the trading name.
            $table->string('slug')->nullable()->unique()->after('company_name');

            // A few sentences in their own words. Not the internal notes, which
            // are the office's and stay private.
            $table->text('public_profile')->nullable()->after('photo_path');

            $table->boolean('is_listed')->default(true)->after('is_available');
        });
    }

    public function down(): void
    {
        Schema::table('assessors', function (Blueprint $table) {
            $table->dropColumn(['slug', 'public_profile', 'is_listed']);
        });
    }
};
