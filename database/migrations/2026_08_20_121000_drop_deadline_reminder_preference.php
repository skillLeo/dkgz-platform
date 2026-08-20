<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * There is no acceptance deadline any more, so there is nothing to remind a
 * partner about before it lapses. The preference goes with the feature.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('assessors', 'notify_deadline_reminder')) {
            Schema::table('assessors', fn (Blueprint $t) => $t->dropColumn('notify_deadline_reminder'));
        }

        DB::table('settings')->where('key', 'business.request_expiry_hours')->delete();
    }

    public function down(): void
    {
        Schema::table('assessors', function (Blueprint $table) {
            $table->boolean('notify_deadline_reminder')->default(true)->after('notify_new_request');
        });
    }
};
