<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per reminder actually sent, so a partner is never told the same thing
 * twice. Without it the daily command would re-send every reminder every day.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liability_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessor_id')->constrained()->cascadeOnDelete();
            $table->date('valid_until');
            $table->unsignedSmallInteger('days_before');
            $table->timestamp('sent_at');
            $table->timestamps();

            // The same partner, the same expiry date, the same milestone: once.
            $table->unique(['assessor_id', 'valid_until', 'days_before'], 'liability_reminder_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liability_reminders');
    }
};
