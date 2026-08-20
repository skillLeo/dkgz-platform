<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the acceptance deadline.
 *
 * Superseded by the client's change request: a request now stays open until a
 * partner accepts it or an administrator closes it by hand. Nothing expires on
 * a timer, so the column, its index and the `expired` status all go — leaving
 * them behind would mean a dead column that future readers assume still means
 * something.
 *
 * Requests already sitting at `expired` become `unanswered`, which is the
 * nearest surviving truth: nobody took them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_requests')->where('status', 'expired')->update(['status' => 'unanswered']);

        // A database created after this change never had the column; only an
        // existing one needs the removal.
        if (Schema::hasColumn('service_requests', 'accept_deadline_at')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropIndex('service_requests_expiry_sweep_index');
                $table->dropColumn('accept_deadline_at');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE service_requests MODIFY COLUMN status
                 ENUM('new','matched','assigned','completed','cancelled','unanswered')
                 NOT NULL DEFAULT 'new'"
            );
        }
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestamp('accept_deadline_at')->nullable()->after('assigned_at');
            $table->index(['status', 'accept_deadline_at'], 'service_requests_expiry_sweep_index');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE service_requests MODIFY COLUMN status
                 ENUM('new','matched','assigned','completed','cancelled','expired','unanswered')
                 NOT NULL DEFAULT 'new'"
            );
        }
    }
};
