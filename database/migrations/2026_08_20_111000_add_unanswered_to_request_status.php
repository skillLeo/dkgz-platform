<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds 'unanswered' to the request status enum on an existing database.
 *
 * A fresh install already has it — the create migration lists it. This exists
 * only for databases created before the state did, which is production.
 * SQLite is skipped deliberately: it is only ever built fresh here, and
 * rebuilding the column there would drop the indexes that depend on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE service_requests MODIFY COLUMN status
             ENUM('new','matched','assigned','completed','cancelled','expired','unanswered')
             NOT NULL DEFAULT 'new'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('service_requests')->where('status', 'unanswered')->update(['status' => 'expired']);

        DB::statement(
            "ALTER TABLE service_requests MODIFY COLUMN status
             ENUM('new','matched','assigned','completed','cancelled','expired')
             NOT NULL DEFAULT 'new'"
        );
    }
};
