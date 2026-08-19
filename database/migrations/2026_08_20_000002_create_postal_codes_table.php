<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postal_codes', function (Blueprint $table) {
            $table->id();
            $table->char('code', 5)->index();
            $table->string('city');
            $table->string('state')->nullable();
            $table->timestamps();

            $table->unique(['code', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postal_codes');
    }
};
