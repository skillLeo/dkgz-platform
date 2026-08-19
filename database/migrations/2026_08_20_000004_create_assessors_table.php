<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->enum('legal_form', [
                'einzelunternehmen', 'gbr', 'gmbh', 'ug', 'ohg', 'kg', 'gmbh_co_kg', 'ag', 'sonstige',
            ])->default('einzelunternehmen');
            $table->string('street')->nullable();
            $table->string('house_number', 20)->nullable();
            $table->char('postal_code', 5)->nullable();
            $table->string('city')->nullable();
            $table->char('country', 2)->default('DE');
            $table->string('vat_id', 20)->nullable();
            $table->string('website')->nullable();
            $table->enum('certification_body', [
                'tuev', 'dekra', 'gtue', 'kues', 'ihk', 'bvsk', 'dat', 'sonstige',
            ])->nullable();
            $table->string('certification_number')->nullable();
            $table->date('certification_valid_until')->nullable();
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->string('qualification_document_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['approval_status', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessors');
    }
};
