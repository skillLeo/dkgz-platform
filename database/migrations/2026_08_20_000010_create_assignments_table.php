<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            // UNIQUE is the hard database-level guarantee that one request can
            // only ever produce one assignment. Second line of defence behind
            // the pessimistic row lock in AcceptAssignmentAction.
            $table->foreignId('service_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained()->restrictOnDelete();
            $table->enum('status', [
                'accepted', 'in_progress', 'documents_uploaded', 'completed', 'cancelled',
            ])->default('accepted');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('fee_cents')->nullable();
            $table->timestamp('fee_entered_at')->nullable();
            $table->text('assessor_notes')->nullable();
            $table->timestamps();

            $table->index(['assessor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
