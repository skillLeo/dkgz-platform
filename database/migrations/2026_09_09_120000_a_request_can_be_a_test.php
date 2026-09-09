<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks a request the office made to see the flow work.
 *
 * There was no safe way to try the form: every submission goes out to every
 * partner covering that postal region, so checking a change meant sending real
 * people a job that does not exist. A request made with the office's own secret
 * postal code is created and confirmed exactly like any other, and stops there —
 * nothing is matched and no partner hears about it.
 *
 * A column rather than a convention. "The ones with postcode 00000" is a rule
 * that lives in somebody's head, and the moment the code changes every old test
 * becomes a real request in every count that reads it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('status');
            // The lists and the counts all filter on it.
            $table->index(['is_test', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['is_test', 'status']);
            $table->dropColumn('is_test');
        });
    }
};
