<?php

namespace App\Support;

use App\Models\ContentBlock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Public page copy, cached per page.
 *
 * Controllers hand a whole page's block set to Inertia in one call, so a page
 * render costs at most one query on a cold cache and none on a warm one.
 */
class Content
{
    public const CACHE_PREFIX = 'dkgz.content.';

    public const CACHE_TTL_MINUTES = 60;

    /** @var array<string, array<string, mixed>> */
    private static array $memo = [];

    public static function isAvailable(): bool
    {
        try {
            return Schema::hasTable('content_blocks');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Every block for one page, nested as section => field => value, which is
     * the shape the Vue pages consume.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function page(string $pageKey): array
    {
        if (isset(self::$memo[$pageKey])) {
            return self::$memo[$pageKey];
        }

        if (! self::isAvailable()) {
            return self::$memo[$pageKey] = [];
        }

        return self::$memo[$pageKey] = Cache::remember(
            self::CACHE_PREFIX.$pageKey,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($pageKey) {
                $blocks = [];

                ContentBlock::forPage($pageKey)->get()->each(function (ContentBlock $block) use (&$blocks) {
                    $value = $block->value;

                    if ($block->type === 'image') {
                        $value = SafeStorage::url($value);
                    }

                    // A switch reaches the page as a real boolean rather than as
                    // the "1" or "0" it is stored as. The string "0" is false in
                    // PHP and true in JavaScript, so an off switch handed over
                    // as text would read as on in every Vue template.
                    //
                    // A switch with nothing in it at all stays null: that is a
                    // switch nobody has set, which is not the same as one turned
                    // off, and only the reader knows which way an unset switch
                    // should fall.
                    if ($block->type === 'boolean') {
                        $blocks[$block->section_key][$block->field_key] = $value === null || $value === ''
                            ? null
                            : $value === '1';

                        return;
                    }

                    // An empty block is present and empty, never missing.
                    // Clearing a field in the admin panel stores null, and a
                    // null reaching the page is indistinguishable from a block
                    // that does not exist — so the template fell back to its
                    // built-in default and the text the operator just deleted
                    // reappeared.
                    $blocks[$block->section_key][$block->field_key] = $value ?? '';
                });

                return $blocks;
            }
        );
    }

    /** Single value by dotted key, e.g. "startseite.hero.ueberschrift". */
    public static function get(string $dottedKey, string $default = ''): string
    {
        [$pageKey, $sectionKey, $fieldKey] = array_pad(explode('.', $dottedKey, 3), 3, null);

        if ($sectionKey === null || $fieldKey === null) {
            return $default;
        }

        $value = self::page($pageKey)[$sectionKey][$fieldKey] ?? null;

        return $value === null || $value === '' ? $default : (string) $value;
    }

    /**
     * A switch by dotted key.
     *
     * Separate from get(), which is typed to a string and would hand back "" for
     * an off switch — indistinguishable from a block that was never seeded, and
     * so the wrong answer for a section that defaults to visible.
     */
    public static function bool(string $dottedKey, bool $default = true): bool
    {
        [$pageKey, $sectionKey, $fieldKey] = array_pad(explode('.', $dottedKey, 3), 3, null);

        if ($sectionKey === null || $fieldKey === null) {
            return $default;
        }

        $value = self::page($pageKey)[$sectionKey][$fieldKey] ?? null;

        // A block that does not exist yet keeps the default, so adding a switch
        // never silently hides the thing it was added to control.
        return match (true) {
            $value === null, $value === '' => $default,
            default => (bool) $value,
        };
    }

    public static function flush(?string $pageKey = null): void
    {
        if ($pageKey !== null) {
            unset(self::$memo[$pageKey]);
            Cache::forget(self::CACHE_PREFIX.$pageKey);

            return;
        }

        self::$memo = [];

        if (! self::isAvailable()) {
            return;
        }

        ContentBlock::query()
            ->distinct()
            ->pluck('page_key')
            ->each(fn (string $key) => Cache::forget(self::CACHE_PREFIX.$key));
    }

    /** Page keys the admin content editor offers, in navigation order. */
    public static function pageKeys(): array
    {
        return [
            'layout' => 'Kopf- und Fußzeile',
            'startseite' => 'Startseite',
            'anfrage' => 'Anfrageformular',
            'bestaetigung' => 'Bestätigungsseite',
            'partner' => 'Für Sachverständige',
            'ablauf' => 'Ablauf',
            'leistungen' => 'Leistungen',
            'ueber-uns' => 'Über uns',
            'kontakt' => 'Kontakt',
            'bewertung' => 'Bewertung',
            'fehler' => 'Fehlerseiten',
            'rechnung' => 'Rechnung (PDF)',
            'cookies' => 'Cookie-Hinweis',
            'staedte' => 'Stadtseiten',
            'faq' => 'FAQ-Seite',
            'ratgeber' => 'Ratgeber',
            'verzeichnis' => 'Sachverständigen-Verzeichnis',
        ];
    }
}
