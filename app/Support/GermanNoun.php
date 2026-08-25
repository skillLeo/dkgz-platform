<?php

namespace App\Support;

/**
 * The grammatical gender of a service name.
 *
 * German makes the word before a noun depend on that noun's gender, so a
 * sentence written around "Unfallgutachten" breaks the moment the same sentence
 * is reused for "Beweissicherung": "zum Unfallgutachten" is right, "zum
 * Beweissicherung" is not. The editable copy on the service pages is written
 * once and reused for every service, which is exactly the situation that
 * exposes it.
 *
 * So each service carries a gender, and the article in the copy is bent to fit
 * it. Almost every service DKGZ arranges is a compound ending in "gutachten",
 * which is neuter, and the endings that decide the rest are among the most
 * regular things in the language — so the gender is guessed here and the
 * operator only has to intervene when the guess is wrong.
 *
 * @see resources/js/Support/placeholders.js — where the article is actually bent
 */
final class GermanNoun
{
    public const MASCULINE = 'm';

    public const FEMININE = 'f';

    public const NEUTER = 'n';

    public const GENDERS = [self::MASCULINE, self::FEMININE, self::NEUTER];

    /** The three genders as an operator sees them, in the admin dropdown. */
    public const LABELS = [
        self::MASCULINE => 'der',
        self::FEMININE => 'die',
        self::NEUTER => 'das',
    ];

    /**
     * Endings that decide a German noun's gender, longest first.
     *
     * These are the reliable ones. "-ung" and "-ion" are feminine essentially
     * without exception; "-chen" is neuter by definition. The loanwords are
     * listed because DKGZ's own vocabulary contains them — "der Check", "der
     * Service" — and an English ending tells you nothing about German gender.
     */
    private const ENDINGS = [
        // Neuter: the head noun of nearly every service DKGZ arranges.
        'gutachten' => self::NEUTER,
        'protokoll' => self::NEUTER,
        'zertifikat' => self::NEUTER,
        'chen' => self::NEUTER,
        'lein' => self::NEUTER,
        'ment' => self::NEUTER,
        'tum' => self::NEUTER,

        // Feminine.
        'bestaetigung' => self::FEMININE,
        'ung' => self::FEMININE,
        'heit' => self::FEMININE,
        'keit' => self::FEMININE,
        'schaft' => self::FEMININE,
        'tion' => self::FEMININE,
        'sion' => self::FEMININE,
        'taet' => self::FEMININE,
        'itis' => self::FEMININE,
        'anz' => self::FEMININE,
        'enz' => self::FEMININE,
        'ie' => self::FEMININE,
        'ur' => self::FEMININE,
        'ik' => self::FEMININE,
        'ei' => self::FEMININE,

        // Masculine, including the loanwords already in use here.
        'service' => self::MASCULINE,
        'check' => self::MASCULINE,
        'report' => self::MASCULINE,
        'test' => self::MASCULINE,
        'bericht' => self::MASCULINE,
        'schaden' => self::MASCULINE,
        'wert' => self::MASCULINE,
        'ismus' => self::MASCULINE,
        'ling' => self::MASCULINE,
        'or' => self::MASCULINE,
    ];

    /**
     * The gender of a service name, guessed from how it ends.
     *
     * Neuter is the fallback rather than masculine — which is the commoner
     * gender in German at large, but not here, where all but two of the
     * services are "…gutachten".
     */
    public static function genderOf(?string $name): string
    {
        $word = self::headNoun($name);

        if ($word === '') {
            return self::NEUTER;
        }

        foreach (self::ENDINGS as $ending => $gender) {
            if (str_ends_with($word, $ending)) {
                return $gender;
            }
        }

        // "-e" is feminine often enough to be worth having, but only once the
        // masculine loanwords above have had their turn — "der Service" ends
        // in one too.
        return str_ends_with($word, 'e') ? self::FEMININE : self::NEUTER;
    }

    /**
     * The part of the name the gender actually hangs off.
     *
     * A German compound takes the gender of its last noun, so
     * "Gebrauchtwagen-Check" is masculine like "Check" and not like "Wagen".
     * Umlauts are folded so the endings above can be written in plain ASCII.
     */
    private static function headNoun(?string $name): string
    {
        $word = mb_strtolower(trim((string) $name));
        $word = strtr($word, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);

        // Take the last part of a hyphenated or spaced name.
        $parts = preg_split('/[\s\-–—\/]+/u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return (string) (end($parts) ?: '');
    }
}
