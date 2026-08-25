<?php

use App\Models\ContentBlock;
use Illuminate\Database\Migrations\Migration;

/**
 * Moves the article inside the braces on the copy already live.
 *
 * "Sie benötigen ein {leistung}" was written for "ein Unfallgutachten" and
 * handed unchanged to "ein Beweissicherung". Now that the article can be bent
 * to the service's gender, the copy has to say so — but only where nobody has
 * rewritten it since, because an operator's wording is theirs and not something
 * a deployment gets to overwrite.
 *
 * Two blocks go entirely: the fourth step on the homepage, which said the same
 * thing as the third from the other side, and the other-cities heading in the
 * service sidebar. Leaving them in would offer the operator fields that no
 * longer appear anywhere.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> key => [was, becomes] */
    private const REWORDED = [
        'startseite.ablauf.text' => [
            'Vier Schritte, keine Zwischenstellen. Die Koordination läuft im Hintergrund — Sie haben genau einen Ansprechpartner.',
            'Drei Schritte, keine Zwischenstellen. Die Koordination läuft im Hintergrund — Sie haben genau einen Ansprechpartner.',
        ],
        'startseite.ablauf.schritt_3_text' => [
            'Der erste verfügbare Partner nimmt den Auftrag an. Alle anderen sind damit informiert.',
            'Der erste verfügbare Partner nimmt den Auftrag an und meldet sich direkt bei Ihnen. Alle anderen sind damit informiert.',
        ],
        'staedte.leistung.einleitung' => [
            'Sie benötigen ein {leistung} in {stadt}? Wir vermitteln Ihnen einen geprüften Kfz-Sachverständigen aus der Region — in der Regel noch am selben Werktag.',
            'Sie benötigen {einen leistung} in {stadt}? Wir vermitteln Ihnen einen geprüften Kfz-Sachverständigen aus der Region — in der Regel noch am selben Werktag.',
        ],
        'staedte.leistung.faq_ueberschrift' => [
            'Häufige Fragen zum {leistung}',
            'Häufige Fragen {zum leistung}',
        ],
        'staedte.leistung.ablauf_ueberschrift' => [
            'So kommen Sie zum {leistung} in {stadt}',
            'So kommen Sie {zum leistung} in {stadt}',
        ],
        'staedte.leistung.schritt_3' => [
            'Der erste verfügbare Sachverständige übernimmt das {leistung} und meldet sich direkt bei Ihnen.',
            'Der erste verfügbare Sachverständige übernimmt {den leistung} und meldet sich direkt bei Ihnen.',
        ],
        // Not everything in that list is a Gutachten — a Gebrauchtwagen-Check
        // and a Beweissicherung sit in it too.
        'staedte.leistung.weitere_leistungen' => [
            'Weitere Gutachten in {stadt}',
            'Weitere Leistungen in {stadt}',
        ],
        'leistungen.detail.ablauf_ueberschrift' => [
            'So läuft die Vermittlung',
            'So kommen Sie {zum leistung}',
        ],
        'leistungen.detail.faq_ueberschrift' => [
            'Häufige Fragen',
            'Häufige Fragen {zum leistung}',
        ],
    ];

    /** Fields whose sections no longer render them. */
    private const RETIRED = [
        'startseite.ablauf.schritt_4_titel',
        'startseite.ablauf.schritt_4_text',
        'staedte.leistung.weitere_staedte',
        'leistungen.detail.staedte',
    ];

    public function up(): void
    {
        foreach (self::REWORDED as $key => [$was, $becomes]) {
            [$page, $section, $field] = explode('.', $key);

            ContentBlock::where('page_key', $page)
                ->where('section_key', $section)
                ->where('field_key', $field)
                ->where('value', $was)
                ->update(['value' => $becomes]);
        }

        foreach (self::RETIRED as $key) {
            [$page, $section, $field] = explode('.', $key);

            ContentBlock::where('page_key', $page)
                ->where('section_key', $section)
                ->where('field_key', $field)
                ->delete();
        }
    }

    /**
     * The wording goes back; the deleted blocks do not, because the seeder is
     * what puts those there and it no longer lists them.
     */
    public function down(): void
    {
        foreach (self::REWORDED as $key => [$was, $becomes]) {
            [$page, $section, $field] = explode('.', $key);

            ContentBlock::where('page_key', $page)
                ->where('section_key', $section)
                ->where('field_key', $field)
                ->where('value', $becomes)
                ->update(['value' => $was]);
        }
    }
};
