<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();          // DKGZ-YYYY-NNNNN
            $table->foreignId('service_type_id')->constrained()->restrictOnDelete();
            $table->char('postal_code', 5);
            $table->string('city');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->string('vehicle_make');
            $table->string('vehicle_model');
            $table->unsignedSmallInteger('vehicle_year')->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->string('vehicle_vin', 17)->nullable();
            $table->text('description')->nullable();
            $table->date('preferred_date')->nullable();
            $table->enum('urgency', ['normal', 'soon', 'urgent'])->nullable();
            $table->enum('status', ['new', 'matched', 'assigned', 'completed', 'cancelled', 'unanswered'])->default('new');
            $table->unsignedInteger('matched_count')->default(0);
            $table->timestamp('assigned_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'postal_code']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
