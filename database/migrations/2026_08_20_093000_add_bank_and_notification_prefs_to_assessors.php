<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Bankverbindung and Benachrichtigungen panels of the portal settings
 * screen had nowhere to store anything.
 *
 * The bank details are for settling the monthly commission invoice by transfer.
 * DKGZ takes no payment through the platform and never debits this account —
 * it is recorded so the partner can be paid out and invoiced correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessors', function (Blueprint $table) {
            $table->string('bank_account_holder')->nullable()->after('website');
            $table->string('bank_iban', 34)->nullable()->after('bank_account_holder');
            $table->string('bank_bic', 11)->nullable()->after('bank_iban');

            // Default on: a partner who never opens settings still gets told
            // about work in their area, which is the point of the platform.
            $table->boolean('notify_new_request')->default(true)->after('is_available');
            $table->boolean('notify_deadline_reminder')->default(true)->after('notify_new_request');
            $table->boolean('notify_commission_statement')->default(true)->after('notify_deadline_reminder');
        });
    }

    public function down(): void
    {
        Schema::table('assessors', function (Blueprint $table) {
            $table->dropColumn([
                'bank_account_holder', 'bank_iban', 'bank_bic',
                'notify_new_request', 'notify_deadline_reminder', 'notify_commission_statement',
            ]);
        });
    }
};
