<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How far people get through the request form.
 *
 * Counters only: a step name and the day it happened, with nothing that could
 * identify anybody — no address, no session, no fingerprint. That is deliberate
 * rather than lazy. Analytics that identify a visitor need consent before they
 * may run, and a funnel that only works for people who accepted a banner tells
 * you about that group rather than about your form. A row here is one anonymous
 * tally mark, so it needs no consent and counts everyone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funnel_events', function (Blueprint $table) {
            $table->id();
            $table->string('step', 32);
            $table->date('day');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            // One row per step per day, incremented rather than appended: a
            // busy month stays a few dozen rows instead of tens of thousands.
            $table->unique(['step', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funnel_events');
    }
};
