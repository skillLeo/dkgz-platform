<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A request aimed at one partner rather than at whoever covers the area.
 *
 * Somebody who has read a partner's profile and pressed the button on it has
 * chosen. Sending that to every assessor in the region would be answering a
 * different question from the one they asked, and would hand the work to
 * whoever replied first — which is exactly what they were trying not to do.
 *
 * The postal code goes optional with it: the shortened form on a profile does
 * not ask, because there is nothing to match — the assessor is already decided
 * and telephones to arrange everything else, as they do on every other route.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('requested_assessor_id')
                ->nullable()
                ->after('service_type_id')
                ->constrained('assessors')
                ->nullOnDelete();

            $table->string('postal_code')->nullable()->change();
            $table->string('city')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_assessor_id');
        });
    }
};
