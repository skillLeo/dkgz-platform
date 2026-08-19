<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('fee_cents');
            // SNAPSHOT of business.commission_rate at the moment of calculation.
            // Never read live: editing the rate must not rewrite history.
            $table->decimal('rate_percent', 5, 2);
            $table->unsignedBigInteger('commission_cents');
            $table->enum('status', ['open', 'invoiced', 'settled', 'waived'])->default('open');
            $table->string('invoice_number')->nullable()->unique();
            $table->string('invoice_path')->nullable();
            $table->timestamp('invoiced_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('assessor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
