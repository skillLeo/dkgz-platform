<?php

namespace App\Support;

use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use Illuminate\Support\Facades\Cache;

/**
 * Which parts of Germany actually have a partner.
 *
 * Built from the union of every approved, available partner's postal ranges, so
 * the homepage map reports real coverage rather than decorating the page with a
 * static graphic that says "bundesweit" whether or not it is true.
 *
 * Keyed by the leading postal digit, which is how German postal regions are
 * laid out — 0 in the east, 8 in the south, 2 in the north.
 */
class CoverageMap
{
    private const CACHE_KEY = 'dkgz.coverage-map';

    private const CACHE_MINUTES = 30;

    /** The ten postal regions, with the places a visitor recognises. */
    public const REGIONS = [
        0 => 'Dresden · Leipzig · Chemnitz',
        1 => 'Berlin · Potsdam · Cottbus',
        2 => 'Hamburg · Bremen · Kiel',
        3 => 'Hannover · Kassel · Braunschweig',
        4 => 'Düsseldorf · Dortmund · Essen',
        5 => 'Köln · Bonn · Aachen',
        6 => 'Frankfurt · Mannheim · Saarbrücken',
        7 => 'Stuttgart · Karlsruhe · Freiburg',
        8 => 'München · Augsburg · Ingolstadt',
        9 => 'Nürnberg · Würzburg · Regensburg',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function regions(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), function () {
            $covered = self::coveredDigits();

            return collect(self::REGIONS)
                ->map(fn (string $places, int $digit) => [
                    'digit' => $digit,
                    'label' => $digit.'0000–'.$digit.'9999',
                    'places' => $places,
                    'covered' => in_array($digit, $covered, true),
                ])
                ->values()
                ->all();
        });
    }

    public static function coveredCount(): int
    {
        return collect(self::regions())->where('covered', true)->count();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Leading digits touched by at least one matchable partner's area.
     *
     * @return array<int, int>
     */
    /**
     * How many partners can actually be sent work in this postal code.
     *
     * Approved, active and available, with their area covering the code — the
     * same test the matching engine applies, so a city page never promises a
     * network that would not answer a request from it.
     */
    public static function partnersFor(string $postalCode): int
    {
        return Cache::remember(
            'dkgz.partners.'.$postalCode,
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => Assessor::matchable()->covering($postalCode)->count(),
        );
    }

    private static function coveredDigits(): array
    {
        $assessorIds = Assessor::matchable()->pluck('id');

        if ($assessorIds->isEmpty()) {
            return [];
        }

        return AssessorServiceArea::whereIn('assessor_id', $assessorIds)
            ->get(['postal_code_from', 'postal_code_to'])
            ->flatMap(function (AssessorServiceArea $area) {
                $from = (int) substr((string) $area->postal_code_from, 0, 1);
                $to = (int) substr((string) $area->postal_code_to, 0, 1);

                return range(min($from, $to), max($from, $to));
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
