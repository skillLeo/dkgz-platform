<?php

use App\Models\ContentBlock;
use Illuminate\Database\Migrations\Migration;

/**
 * Removes the wording for a section the city pages no longer have.
 *
 * The introduction and the three notes beside it were one section too many on a
 * page already carrying the services, the steps and the questions. Left in the
 * table they would sit in the admin panel offering fields that appear nowhere,
 * which is how an operator comes to spend an afternoon editing text nobody will
 * ever read.
 */
return new class extends Migration
{
    private const RETIRED = [
        'einleitung_ueberschrift',
        'einleitung_text',
        'hinweise_ueberschrift',
        'hinweis_1_titel', 'hinweis_1_text',
        'hinweis_2_titel', 'hinweis_2_text',
        'hinweis_3_titel', 'hinweis_3_text',
    ];

    public function up(): void
    {
        ContentBlock::where('page_key', 'staedte')
            ->where('section_key', 'stadt')
            ->whereIn('field_key', self::RETIRED)
            ->delete();
    }

    /** The seeder no longer lists them, so there is nothing to put back. */
    public function down(): void
    {
        //
    }
};
