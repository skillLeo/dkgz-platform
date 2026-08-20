<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liability cover expires, and an expired partner must stop receiving work.
 * Without a date the platform cannot tell — it would keep matching someone
 * whose insurance lapsed months ago, which is the one compliance failure this
 * business genuinely cannot absorb.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessor_documents', function (Blueprint $table) {
            $table->date('valid_until')->nullable()->after('mime_type');
            $table->index(['type', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::table('assessor_documents', function (Blueprint $table) {
            $table->dropIndex(['type', 'valid_until']);
            $table->dropColumn('valid_until');
        });
    }
};
