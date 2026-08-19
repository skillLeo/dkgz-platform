<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessor_service_type', function (Blueprint $table) {
            $table->foreignId('assessor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();

            $table->primary(['assessor_id', 'service_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessor_service_type');
    }
};
