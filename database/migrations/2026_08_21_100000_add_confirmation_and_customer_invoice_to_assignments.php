<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the moment a job actually came about, and what the assessor invoiced
 * their customer or the insurer for it.
 *
 * Acceptance and confirmation were the same event before this, which is why the
 * DKGZ fee could only be billed at completion — sometimes weeks later. Keeping
 * the two apart is what lets the invoice go out when the work is real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('accepted_at');

            // Cents, like every other amount here; a float would round money.
            $table->unsignedBigInteger('customer_invoice_cents')->nullable()->after('fee_entered_at');
            $table->string('customer_invoice_recipient', 20)->nullable()->after('customer_invoice_cents');
            $table->string('customer_invoice_number', 60)->nullable()->after('customer_invoice_recipient');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn([
                'confirmed_at',
                'customer_invoice_cents',
                'customer_invoice_recipient',
                'customer_invoice_number',
            ]);
        });
    }
};
