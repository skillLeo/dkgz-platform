<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the percentage commission with a fixed fee per assessment type.
 *
 * Client change request, section 6. Historical rows keep their percentage
 * values untouched — `fee_type` records which model each row was created
 * under, so the register can display both honestly rather than pretending
 * old commissions were always fixed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->unsignedInteger('dkgz_fee_cents')->nullable()->after('sort_order');
        });

        Schema::table('assignments', function (Blueprint $table) {
            // Snapshotted on acceptance: the fee shown when the partner said
            // yes is the fee they owe, even if the admin edits it afterwards.
            $table->unsignedInteger('dkgz_fee_snapshot_cents')->nullable()->after('fee_cents');
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->string('fee_type', 12)->default('percentage')->after('assessor_id');
            $table->unsignedInteger('dkgz_fee_cents')->nullable()->after('fee_type');
        });

        // Everything that exists today was earned under the old model.
        DB::table('commissions')->update(['fee_type' => 'percentage']);

        // Fixed-fee rows have no rate, so the column can no longer be required.
        // Existing values are left exactly as they are.
        // Fixed-fee rows carry neither a rate nor, necessarily, the assessor's
        // own invoice amount — that became optional record-keeping.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE commissions MODIFY COLUMN rate_percent DECIMAL(5,2) NULL');
            DB::statement('ALTER TABLE commissions MODIFY COLUMN fee_cents BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('service_types', fn (Blueprint $t) => $t->dropColumn('dkgz_fee_cents'));
        Schema::table('assignments', fn (Blueprint $t) => $t->dropColumn('dkgz_fee_snapshot_cents'));
        Schema::table('commissions', fn (Blueprint $t) => $t->dropColumn(['fee_type', 'dkgz_fee_cents']));
    }
};
