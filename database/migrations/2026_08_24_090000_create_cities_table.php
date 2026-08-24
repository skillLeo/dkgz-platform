<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The cities that get their own pages.
 *
 * A deliberately short, hand-kept list rather than every place in the postal
 * table: a page for each of nine services in every German town would be
 * thousands of pages saying almost the same thing, which search engines treat
 * as thin content and rank accordingly. Ten to twenty real cities, each saying
 * something true about that city, is worth more than all of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('state', 80)->nullable();
            $table->string('postal_code', 5)->nullable();

            // Copy for the page. Empty is allowed: the template falls back to
            // wording built from the city and service names, so a city is
            // useful the moment it is created and can be improved later.
            $table->string('headline', 200)->nullable();
            $table->text('intro')->nullable();
            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 320)->nullable();

            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Which services are offered in which city. A city with none offered
        // has no pages at all, which is how a city is taken offline without
        // deleting the copy written for it.
        Schema::create('city_service_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['city_id', 'service_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_service_type');
        Schema::dropIfExists('cities');
    }
};
