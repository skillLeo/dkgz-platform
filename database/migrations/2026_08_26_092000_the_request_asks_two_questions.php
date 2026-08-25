<?php

use App\Models\ContentBlock;
use Illuminate\Database\Migrations\Migration;

/**
 * Retires the copy for the fields the request no longer has.
 *
 * The middle step asked for the make, the model, the year, the plate, a
 * description and photographs, and each of those had its own editable heading.
 * Left in place they would sit in the admin panel offering wording for a screen
 * nobody will ever see.
 *
 * The wording that survives is edited rather than replaced, because the
 * operator has been through this page and some of it is theirs: the button now
 * ends the request rather than moving to the next step, and the note under it
 * is where the consent wording lives now that there is no tick box.
 */
return new class extends Migration
{
    private const RETIRED = [
        'abschnitt_anliegen',
        'abschnitt_kontakt',
        'abschnitt_optional',
        'schritt_3_titel',
        'schritt_3_text',
    ];

    /** The sidebar went with the second column; the telephone line did not. */
    private const RETIRED_SIDEBAR = ['punkt_1', 'punkt_2', 'punkt_3'];

    /** field => [only if it still says this, becomes this] */
    private const REWORDED = [
        'schritt_2_titel' => [
            'Angaben zum Fahrzeug',
            'Fast geschafft!',
        ],
        'schritt_2_text' => [
            'Marke und Modell genügen. Fotos und eine kurze Schilderung helfen dem Sachverständigen bei der Einschätzung, sind aber freiwillig.',
            'Bitte vervollständigen Sie Ihre Daten, damit wir einen passenden Gutachter für Sie vermitteln können.',
        ],
        'cta' => [
            'Anfrage absenden',
            'Kostenfrei anfragen',
        ],
        'kurzhinweis' => [
            'Kostenlos und unverbindlich',
            'Ihre Anfrage ist kostenfrei und unverbindlich. Es entstehen für Sie keine Kosten.',
        ],
        'datenschutzhinweis' => [
            'Ihre Daten werden ausschließlich an den Sachverständigen übermittelt, der den Auftrag annimmt. Es entstehen keine Kosten und keine Verpflichtung.',
            'Mit dem Absenden willigen Sie ein, dass DKGZ Ihre Angaben zur Vermittlung an geeignete Sachverständige verarbeitet. Ihre Daten gehen ausschließlich an den Sachverständigen, der den Auftrag annimmt.',
        ],
    ];

    /** The one line outside the form section that counted the old fields. */
    private const HEADING = [
        'Sieben Pflichtangaben. Ihre Kontaktdaten sieht ausschließlich der Sachverständige, der den Auftrag annimmt.',
        'Zwei Schritte. Ihre Kontaktdaten sieht ausschließlich der Sachverständige, der den Auftrag annimmt.',
    ];

    public function up(): void
    {
        ContentBlock::where('page_key', 'anfrage')
            ->where('section_key', 'formular')
            ->whereIn('field_key', self::RETIRED)
            ->delete();

        ContentBlock::where('page_key', 'anfrage')
            ->where('section_key', 'seitenleiste')
            ->whereIn('field_key', self::RETIRED_SIDEBAR)
            ->delete();

        foreach (self::REWORDED as $field => [$was, $becomes]) {
            ContentBlock::where('page_key', 'anfrage')
                ->where('section_key', 'formular')
                ->where('field_key', $field)
                ->where('value', $was)
                ->update(['value' => $becomes]);
        }

        ContentBlock::where('page_key', 'anfrage')
            ->where('section_key', 'kopf')
            ->where('field_key', 'text')
            ->where('value', self::HEADING[0])
            ->update(['value' => self::HEADING[1]]);
    }

    /** The wording goes back; the seeder no longer lists the deleted blocks. */
    public function down(): void
    {
        foreach (self::REWORDED as $field => [$was, $becomes]) {
            ContentBlock::where('page_key', 'anfrage')
                ->where('section_key', 'formular')
                ->where('field_key', $field)
                ->where('value', $becomes)
                ->update(['value' => $was]);
        }
    }
};
