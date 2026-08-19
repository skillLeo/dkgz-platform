<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['report', 'customer_invoice', 'other']);
            $table->string('path');            // PRIVATE disk, served only via controller
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_documents');
    }
};
