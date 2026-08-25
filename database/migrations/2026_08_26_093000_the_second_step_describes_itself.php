<?php

use App\Models\ContentBlock;
use Illuminate\Database\Migrations\Migration;

/**
 * Two lines the operator had rewritten, which now describe the wrong screen.
 *
 * The previous migration only touched wording still identical to what was
 * seeded, which is the right instinct — an operator's words are theirs. But the
 * second step used to be about the car and is now about the person, and a line
 * reading "Geben Sie die wichtigsten Fahrzeugdaten an" above a name, an e-mail
 * address and a telephone number is not their wording being respected, it is
 * the page lying about what it is asking for.
 *
 * The note under the button is the other one. It carried a reassurance and
 * nothing else, which was fine beneath a consent tick box and is not fine now
 * that the tick box is gone and this line is where the consent is given. Their
 * sentence is kept and the consent wording put in front of it, rather than
 * their sentence being thrown away.
 */
return new class extends Migration
{
    private const CONSENT = 'Mit dem Absenden willigen Sie ein, dass DKGZ Ihre Angaben zur Vermittlung an geeignete Sachverständige verarbeitet.';

    private const CONTACT_STEP = 'Bitte vervollständigen Sie Ihre Daten, damit wir einen passenden Gutachter für Sie vermitteln können.';

    public function up(): void
    {
        $intro = $this->block('schritt_2_text');

        // Anything still describing the vehicle step describes a screen that is
        // no longer there.
        if ($intro && preg_match('/fahrzeug|foto|marke|modell|kennzeichen/iu', (string) $intro->value)) {
            $intro->update(['value' => self::CONTACT_STEP]);
        }

        $note = $this->block('datenschutzhinweis');

        if ($note && ! str_contains(mb_strtolower((string) $note->value), 'willigen sie ein')) {
            $note->update(['value' => trim(self::CONSENT.' '.trim((string) $note->value))]);
        }
    }

    /** Not reversible: the wording put back would not be the operator's own. */
    public function down(): void
    {
        //
    }

    private function block(string $field): ?ContentBlock
    {
        return ContentBlock::where('page_key', 'anfrage')
            ->where('section_key', 'formular')
            ->where('field_key', $field)
            ->first();
    }
};
