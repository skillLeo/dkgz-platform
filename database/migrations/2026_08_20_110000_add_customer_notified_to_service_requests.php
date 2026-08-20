<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records that the customer was told nothing came of their request.
 *
 * Without this the office cannot tell an unanswered request that has been
 * explained to the customer from one where they are still sitting in silence,
 * which is the difference between a handled case and an abandoned one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestamp('customer_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('customer_notified_at');
        });
    }
};
