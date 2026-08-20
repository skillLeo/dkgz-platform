<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Explanatory copy per assessment type.
 *
 * A visitor who cannot tell a Haftpflichtgutachten from a Kaskogutachten will
 * either pick wrong or leave; the description field alone was a sentence. Each
 * section is separately editable so the client can revise one without touching
 * the rest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->text('includes_de')->nullable()->after('description_de');
            $table->text('target_audience_de')->nullable()->after('includes_de');
            $table->text('typical_situations_de')->nullable()->after('target_audience_de');
            $table->text('differences_de')->nullable()->after('typical_situations_de');
            $table->text('additional_info_de')->nullable()->after('differences_de');
            // Seeded starter copy is a draft, not advice. Cleared on first save.
            $table->boolean('content_is_placeholder')->default(true)->after('additional_info_de');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', fn (Blueprint $table) => $table->dropColumn([
            'includes_de', 'target_audience_de', 'typical_situations_de',
            'differences_de', 'additional_info_de', 'content_is_placeholder',
        ]));
    }
};
