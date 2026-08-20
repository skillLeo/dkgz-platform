<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The acceptance deadline shown in the portal as "Frist zur Annahme". It was
 * previously derived inside the notification job and thrown away, so nothing
 * could display or enforce it. Persisting it makes the deadline a fact about
 * the request rather than an accident of when the e-mail happened to be built.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestamp('accept_deadline_at')->nullable()->after('assigned_at');
            $table->index(['status', 'accept_deadline_at'], 'service_requests_expiry_sweep_index');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('service_requests_expiry_sweep_index');
            $table->dropColumn('accept_deadline_at');
        });
    }
};
