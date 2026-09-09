<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "für Ihr Unfallgutachten", but "für Ihre Fahrzeugbewertung".
 *
 * The line was written around one assessment and handed unchanged to all seven,
 * so a feminine one read "für Ihr Fahrzeugbewertung". The article moves inside
 * the braces, where it is bent to the gender of the service that replaces it —
 * the same fix the service pages already had.
 *
 * Only where nobody has rewritten it since. An operator's wording is theirs and
 * not something a deployment gets to overwrite; if they have edited the line,
 * they keep it exactly as they wrote it and the article stays unbent until they
 * choose otherwise.
 */
return new class extends Migration
{
    private const WAS = 'Passende Kfz-Gutachter für Ihr {leistung} sind deutschlandweit verfügbar.';

    private const NOW = 'Passende Kfz-Gutachter für {Ihren leistung} sind deutschlandweit verfügbar.';

    public function up(): void
    {
        DB::table('content_blocks')
            ->where([
                'page_key' => 'anfrage',
                'section_key' => 'formular',
                'field_key' => 'verfuegbarkeit',
                'value' => self::WAS,
            ])
            ->update(['value' => self::NOW]);
    }

    public function down(): void
    {
        DB::table('content_blocks')
            ->where([
                'page_key' => 'anfrage',
                'section_key' => 'formular',
                'field_key' => 'verfuegbarkeit',
                'value' => self::NOW,
            ])
            ->update(['value' => self::WAS]);
    }
};
