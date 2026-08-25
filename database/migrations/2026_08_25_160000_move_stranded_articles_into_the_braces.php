<?php

use App\Models\ContentBlock;
use App\Support\GermanNoun;
use Illuminate\Database\Migrations\Migration;

/**
 * Converts copy the operator wrote before the article could be bent.
 *
 * The previous migration only touched wording still identical to what was
 * seeded, which is right — an operator's words are theirs. But two of the
 * strings they had rewritten still carry the bug being fixed: "Sie benötigen
 * ein {leistung}" and "übernimmt das {leistung}" are correct for a Gutachten
 * and wrong for every service that is not one.
 *
 * So the article is moved inside the braces without touching anything else in
 * the sentence. Which masculine form to use comes from where the article sits:
 * one written in lower case is inside a sentence, where a service name is the
 * object of a verb — "Sie benötigen ein …", "übernimmt das …" — and one written
 * with a capital opens the sentence, where it is the subject. That covers what
 * is actually written on these pages; anything else is left standing, and the
 * help text under each field now explains the syntax for whatever gets written
 * next.
 *
 * @see GermanNoun
 * @see resources/js/Support/placeholders.js
 */
return new class extends Migration
{
    /** Article as written => the masculine form that says which case it is. */
    private const OBJECT = [
        'ein' => 'einen', 'eine' => 'einen', 'einen' => 'einen',
        'das' => 'den', 'die' => 'den', 'den' => 'den',
        'dem' => 'dem', 'der' => 'dem',
        'zum' => 'zum', 'zur' => 'zum',
        'beim' => 'beim',
        'vom' => 'vom',
        'im' => 'im',
    ];

    /** The same, for an article opening a sentence. */
    private const SUBJECT = [
        'Ein' => 'Ein', 'Eine' => 'Ein',
        'Das' => 'Der', 'Die' => 'Der', 'Der' => 'Der',
    ];

    public function up(): void
    {
        $written = implode('|', array_merge(array_keys(self::SUBJECT), array_keys(self::OBJECT)));

        ContentBlock::query()
            ->where('value', 'like', '%{leistung}%')
            ->get()
            ->each(function (ContentBlock $block) use ($written) {
                $bent = preg_replace_callback(
                    "/\b({$written})\s+\{leistung\}/u",
                    fn (array $m) => '{'.(self::SUBJECT[$m[1]] ?? self::OBJECT[mb_strtolower($m[1])]).' leistung}',
                    (string) $block->value
                );

                if ($bent !== $block->value) {
                    $block->update(['value' => $bent]);
                }
            });
    }

    /**
     * Not reversible: which article was written before the move cannot be
     * recovered from the masculine form it was folded into.
     */
    public function down(): void
    {
        //
    }
};
