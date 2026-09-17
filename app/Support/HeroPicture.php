<?php

namespace App\Support;

use App\Models\City;
use App\Models\ServiceType;

/**
 * The picture beside the headline on a service page, a city page, and a page
 * for one service in one city.
 *
 * Each page takes the most specific picture there is and works outwards from
 * it: the service's or the city's own, then the default somebody set for that
 * kind of page in Seiteninhalte, then the homepage's. The last one is always
 * there, so no page is ever left with the empty half it had before — and
 * pictures can be added one service or one city at a time without the others
 * changing.
 *
 * The size belongs to the picture. An uploaded picture keeps the size set next
 * to it; without one it takes the size set for that kind of page, and without
 * that the homepage's. A page showing the default or the homepage picture takes
 * the size for its kind of page, so a size set on one city never reaches a page
 * that is not showing that city's picture.
 *
 * Whether it shows on a phone and the seal card on its corner still come from
 * the homepage, so the pictures read as one family.
 */
class HeroPicture
{
    /** The range every size is held to, in percent of the standard. */
    public const MIN_SIZE = 60;

    public const MAX_SIZE = 140;

    /** @return array<string, mixed> */
    public static function forService(ServiceType $service): array
    {
        return self::payload($service->imageUrl(), $service->image_size, 'leistungen.detail');
    }

    /** @return array<string, mixed> */
    public static function forCity(City $city): array
    {
        return self::payload($city->imageUrl(), $city->image_size, 'staedte.stadt');
    }

    /**
     * The service comes first: "Unfallgutachten in Düsseldorf" is a page about
     * the assessment, and the city is where it happens.
     *
     * @return array<string, mixed>
     */
    public static function forCityService(City $city, ServiceType $service): array
    {
        return $service->imageUrl() !== null
            ? self::payload($service->imageUrl(), $service->image_size, 'staedte.leistung')
            : self::payload($city->imageUrl(), $city->image_size, 'staedte.leistung');
    }

    /**
     * The size a kind of page gives a picture that has none of its own.
     *
     * Also what the admin panel shows beside an upload whose size is still
     * empty, so the slider starts where the page actually is.
     */
    public static function defaultSize(string $page): string
    {
        return self::filled(Content::get("{$page}.bild_groesse"))
            ?? Content::get('startseite.hero.bild_groesse', '100');
    }

    /** @return array<string, mixed> */
    private static function payload(?string $ownSrc, ?int $ownSize, string $page): array
    {
        $src = $ownSrc
            ?? self::filled(Content::get("{$page}.bild"))
            ?? self::filled(Content::get('startseite.hero.bild'));

        $size = $ownSrc !== null && $ownSize !== null
            ? (string) $ownSize
            : self::defaultSize($page);

        return [
            'src' => $src,
            'size' => $size,
            'on_mobile' => Content::bool('startseite.hero.bild_mobil', false),
            'seal_title' => Content::get('startseite.hero.siegel_titel'),
            'seal_text' => Content::get('startseite.hero.siegel_text'),
        ];
    }

    private static function filled(string $value): ?string
    {
        return trim($value) === '' ? null : $value;
    }
}
