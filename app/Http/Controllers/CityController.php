<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\ServiceType;
use App\Support\Content;
use App\Support\CoverageMap;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing pages for a city, and for one service within it.
 *
 * These exist to be found: somebody searching "Unfallgutachten Düsseldorf"
 * should land on a page about exactly that rather than a general services page
 * they have to navigate. Which means each page has to say something true about
 * that city — the service's own description, how many partners cover the
 * postal region, the operator's own words where they have written any — and not
 * merely swap a name into a sentence.
 */
class CityController extends Controller
{
    /** Every city that has pages, for the overview and internal linking. */
    public function index(): Response
    {
        return Inertia::render('Public/Staedte', [
            'content' => Content::page('staedte'),
            'cities' => City::active()->ordered()
                ->withCount('publishedServiceTypes')
                ->get(['id', 'name', 'slug', 'state'])
                ->map(fn (City $city) => [
                    'name' => $city->name,
                    'state' => $city->state,
                    'url' => "/kfz-gutachter/{$city->slug}",
                    'services' => $city->published_service_types_count,
                ]),
        ]);
    }

    /** One city: what can be arranged there, and who is nearby. */
    public function show(City $city): Response
    {
        abort_unless($city->is_active, 404);

        $services = $city->publishedServiceTypes()->ordered()->get();

        // A city offering nothing has no page: an empty landing page is worse
        // than none at all, for the visitor and for the search engine.
        abort_if($services->isEmpty(), 404);

        return Inertia::render('Public/Stadt', [
            'content' => Content::page('staedte'),
            'city' => $this->cityPayload($city),
            'services' => $services->map(fn (ServiceType $type) => [
                'name' => $type->name_de,
                'description' => $type->description_de,
                'icon' => $type->icon,
                'url' => "/kfz-gutachter/{$city->slug}/{$type->slug}",
            ]),
        ]);
    }

    /** One service in one city — the page these all exist for. */
    public function service(City $city, ServiceType $serviceType): Response
    {
        abort_unless($city->is_active && $serviceType->is_active, 404);
        abort_unless($city->serviceTypes()->whereKey($serviceType->getKey())->exists(), 404);

        return Inertia::render('Public/StadtLeistung', [
            'content' => Content::page('staedte'),
            'city' => $this->cityPayload($city),
            'serviceType' => $serviceType->only([
                'name_de', 'description_de', 'slug', 'icon', 'includes_de',
                'target_audience_de', 'typical_situations_de',
                'differences_de', 'additional_info_de',
            ]),
            // The other services here, so every page links onward rather than
            // being a dead end.
            'otherServices' => $city->publishedServiceTypes()
                ->ordered()
                ->whereKeyNot($serviceType->getKey())
                ->get()
                ->map(fn (ServiceType $type) => [
                    'name' => $type->name_de,
                    'url' => "/kfz-gutachter/{$city->slug}/{$type->slug}",
                ]),
            // And the same service in other cities.
            'otherCities' => City::active()->ordered()
                ->whereKeyNot($city->getKey())
                ->whereHas('serviceTypes', fn ($q) => $q->whereKey($serviceType->getKey()))
                ->limit(8)
                ->get(['name', 'slug'])
                ->map(fn (City $other) => [
                    'name' => $other->name,
                    'url' => "/kfz-gutachter/{$other->slug}/{$serviceType->slug}",
                ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function cityPayload(City $city): array
    {
        return [
            'name' => $city->name,
            'slug' => $city->slug,
            'state' => $city->state,
            'label' => $city->label(),
            'postal_code' => $city->postal_code,
            'headline' => $city->headline,
            'intro' => $city->intro,
            'meta_title' => $city->meta_title,
            'meta_description' => $city->meta_description,
            'url' => "/kfz-gutachter/{$city->slug}",
            // Honest about the network: the number of approved, available
            // partners whose area covers this city's postal region.
            'partners' => $city->postal_code
                ? CoverageMap::partnersFor($city->postal_code)
                : null,
        ];
    }
}
