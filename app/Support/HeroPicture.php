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
 * Everything else about the frame comes from the homepage — its size, whether
 * it shows on a phone, and the seal card on its corner — so the pictures look
 * like one family and there is one place to change them.
 */
class HeroPicture
{
    /** @return array<string, mixed> */
    public static function forService(ServiceType $service): array
    {
        return self::payload($service->imageUrl(), 'leistungen.detail.bild');
    }

    /** @return array<string, mixed> */
    public static function forCity(City $city): array
    {
        return self::payload($city->imageUrl(), 'staedte.stadt.bild');
    }

    /**
     * The service comes first: "Unfallgutachten in Düsseldorf" is a page about
     * the assessment, and the city is where it happens.
     *
     * @return array<string, mixed>
     */
    public static function forCityService(City $city, ServiceType $service): array
    {
        return self::payload($service->imageUrl() ?? $city->imageUrl(), 'staedte.leistung.bild');
    }

    /** @return array<string, mixed> */
    private static function payload(?string $own, string $defaultKey): array
    {
        $src = $own
            ?? self::filled(Content::get($defaultKey))
            ?? self::filled(Content::get('startseite.hero.bild'));

        return [
            'src' => $src,
            'size' => Content::get('startseite.hero.bild_groesse'),
            'on_mobile' => Content::bool('startseite.hero.bild_mobil', false),
            'seal_title' => Content::get('startseite.hero.siegel_titel'),
            'seal_text' => Content::get('startseite.hero.siegel_text'),
        ];
    }

    private static function filled(string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
