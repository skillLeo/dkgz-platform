<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The request form stops asking what the car is.
 *
 * Make and model were required, which put a screen of vehicle questions between
 * somebody arriving from an advert and the point where DKGZ has their telephone
 * number. The assessor rings them anyway, and asks then. Everything the
 * matching actually uses — the service and the postal code — is still asked
 * first.
 *
 * The columns stay: the office takes requests by telephone and fills them in,
 * and every request already recorded keeps what it has.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('vehicle_make')->nullable()->change();
            $table->string('vehicle_model')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('vehicle_make')->nullable(false)->change();
            $table->string('vehicle_model')->nullable(false)->change();
        });
    }
};
