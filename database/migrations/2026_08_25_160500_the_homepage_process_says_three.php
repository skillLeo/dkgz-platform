<?php

use App\Models\ContentBlock;
use Illuminate\Database\Migrations\Migration;

/**
 * The sentence above the process, and the step that lost its ending.
 *
 * Dropping the fourth step leaves two things behind on any site where the
 * operator has rewritten this section, which is exactly the case the earlier
 * migration deliberately would not touch: an intro that still counts four
 * steps, and a third step that no longer says the assessor gets in touch —
 * which was the whole content of the step that went.
 *
 * Both are edited in place rather than replaced, so the operator's own wording
 * survives. Neither runs if the text already reads correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        $intro = ContentBlock::query()
            ->where('page_key', 'startseite')
            ->where('section_key', 'ablauf')
            ->where('field_key', 'text')
            ->first();

        // "In 4 einfachen Schritten", "Vier Schritte" — whichever way it was
        // put, the count is now three.
        if ($intro && preg_match('/\b(4|vier)\b/iu', (string) $intro->value)) {
            $intro->update(['value' => preg_replace_callback(
                '/\b(4|vier)\b/iu',
                fn (array $m) => match (mb_strtolower($m[1])) {
                    '4' => '3',
                    'vier' => $m[1] === 'Vier' ? 'Drei' : 'drei',
                },
                (string) $intro->value
            )]);
        }

        $third = ContentBlock::query()
            ->where('page_key', 'startseite')
            ->where('section_key', 'ablauf')
            ->where('field_key', 'schritt_3_text')
            ->first();

        if ($third && ! str_contains(mb_strtolower((string) $third->value), 'meldet sich')) {
            $third->update([
                'value' => rtrim((string) $third->value).' Er meldet sich anschließend direkt bei Ihnen.',
            ]);
        }
    }

    /** Not reversible: the wording edited here was the operator's, not ours. */
    public function down(): void
    {
        //
    }
};
