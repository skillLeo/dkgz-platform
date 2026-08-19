<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained()->cascadeOnDelete();
            $table->enum('outcome', ['pending', 'accepted', 'declined', 'closed', 'expired'])->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->unique(['service_request_id', 'assessor_id']);
            $table->index(['assessor_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_matches');
    }
};
