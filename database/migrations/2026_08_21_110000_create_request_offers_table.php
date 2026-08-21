<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A request offered by hand to somebody who is not on the platform yet.
 *
 * The matching engine can only reach assessors who have already registered, so
 * a request in a region nobody covers had no route forward except waiting. An
 * admin can now send it straight to an assessor they know of; that person
 * accepts from the e-mail and registers afterwards, which is the opposite order
 * from everybody else and the reason this needs its own table rather than a row
 * in request_matches — there is no assessor_id to put there yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();

            // The whole authority of the link: long, random, and unique.
            $table->string('token', 64)->unique();

            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();

            // Filled once the invitee registers and the assignment is created.
            $table->foreignId('assessor_id')->nullable()->constrained()->nullOnDelete();

            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('decline_reason')->nullable();

            // An acceptance holds the request only for as long as this: an
            // invitee who never finishes registering must not park a customer's
            // request forever.
            $table->timestamp('hold_until')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['service_request_id', 'accepted_at']);
            $table->index(['email', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_offers');
    }
};
