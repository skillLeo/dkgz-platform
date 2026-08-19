<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->enum('group', [
                'branding', 'contact', 'email', 'integrations', 'business', 'seo', 'legal', 'features',
            ]);
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->enum('type', [
                'string', 'text', 'boolean', 'integer', 'decimal', 'json', 'file', 'encrypted',
            ])->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->string('label_de');
            $table->text('help_de')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
