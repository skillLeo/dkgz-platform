<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Articles: the Ratgeber.
 *
 * Everything else on the site answers somebody who has already decided they
 * need an assessor. This is for the person a week earlier, typing "wer zahlt
 * das gutachten nach unfall" into Google — a question DKGZ can answer properly
 * and nobody else on the site is answering at all.
 *
 * The address follows the title, the way city and service pages follow their
 * names, so publishing an article never means anybody typing a URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category')->nullable();

            // The card and the opening paragraph. Kept apart from the body so a
            // listing never has to guess where to cut an article off.
            $table->string('excerpt', 400)->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_alt')->nullable();

            // Whose name goes on it. Free text rather than a user relation: the
            // office publishes under the firm's name as often as a person's.
            $table->string('author')->nullable();

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();

            $table->boolean('is_published')->default(false);
            // When it says it was written, which is not when the row was made:
            // an article can be dated and scheduled, or backdated on import.
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
