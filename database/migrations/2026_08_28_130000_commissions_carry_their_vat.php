<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The tax on a referral fee, recorded on the invoice that charged it.
 *
 * Stored rather than worked out on the fly, and stored at the moment the
 * invoice is issued. A rate is a fact about a date: if the German rate changes
 * next year, every invoice already sent must go on saying nineteen per cent,
 * because that is what the partner was billed and what both sides have in their
 * books. Recomputing from a setting would quietly rewrite last year's accounts.
 *
 * commission_cents keeps its meaning — the net fee — so nothing that has ever
 * summed it changes underneath.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->decimal('vat_percent', 5, 2)->nullable()->after('commission_cents');
            $table->unsignedBigInteger('vat_cents')->nullable()->after('vat_percent');
            $table->unsignedBigInteger('gross_cents')->nullable()->after('vat_cents');
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn(['vat_percent', 'vat_cents', 'gross_cents']);
        });
    }
};
