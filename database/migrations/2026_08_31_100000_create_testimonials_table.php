<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What customers say, in their own words and with their own faces.
 *
 * Every other reassurance on the homepage is DKGZ talking about DKGZ. This is
 * the one place somebody else does the talking, which is why it is worth the
 * table rather than three more content blocks: a quote has a person attached,
 * and a person has a name, a town and a face.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Where they are, which is what makes "bundesweit" believable.
            $table->string('location')->nullable();
            $table->text('quote');
            $table->string('photo_path')->nullable();
            // Out of five, or null where the operator would rather not show
            // stars against a quote somebody gave them in a telephone call.
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
