<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a page should be indexed at all.
 *
 * Deliberately the only thing this table holds. Titles and descriptions already
 * live somewhere — a content block, a city's own column, an article's — and
 * copying them here would create two sources for one string and a daily
 * argument about which is right. The SEO screen reads and writes those in
 * place; this records the one decision that has nowhere else to live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->boolean('is_indexed')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};
