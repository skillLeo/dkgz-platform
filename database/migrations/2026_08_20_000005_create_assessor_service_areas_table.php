<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessor_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessor_id')->constrained()->cascadeOnDelete();
            $table->char('postal_code_from', 5);
            $table->char('postal_code_to', 5);
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['postal_code_from', 'postal_code_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessor_service_areas');
    }
};
