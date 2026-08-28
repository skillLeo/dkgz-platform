<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The cities that have their own pages, and which services they show.
 *
 * Kept deliberately manual. A page for every service in every place in the
 * postal table would be thousands of near-identical pages, which search engines
 * treat as thin content and rank accordingly; a short list of real cities, each
 * saying something true, is worth more than all of them.
 */
class CityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', City::class);

        return Inertia::render('Admin/Staedte', [
            'cities' => City::ordered()
                ->with('serviceTypes:id')
                ->get()
                ->map(fn (City $city) => [
                    'id' => $city->id,
                    'name' => $city->name,
                    'slug' => $city->slug,
                    'state' => $city->state,
                    'postal_code' => $city->postal_code,
                    'headline' => $city->headline,
                    'intro' => $city->intro,
                    'body' => $city->body,
                    'faqs' => $city->faqs ?? [],
                    'meta_title' => $city->meta_title,
                    'meta_description' => $city->meta_description,
                    'is_active' => $city->is_active,
                    'service_type_ids' => $city->serviceTypes->pluck('id')->all(),
                    'url' => "/kfz-gutachter/{$city->slug}",
                    // How many pages this city actually publishes: the hub plus
                    // one per service, or none at all while it is switched off.
                    'page_count' => $city->is_active
                        ? $city->serviceTypes->count() + 1
                        : 0,
                ]),
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'name_de']),
            'canEdit' => $request->user()->can('cities.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', City::class);

        $city = City::create($this->validated($request));
        $city->serviceTypes()->sync($request->input('service_type_ids', []));

        return back()->with('success', "{$city->name} wurde angelegt.");
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $this->authorize('update', $city);

        $city->update($this->validated($request, $city));
        $city->serviceTypes()->sync($request->input('service_type_ids', []));

        return back()->with('success', "{$city->name} wurde gespeichert.");
    }

    public function destroy(Request $request, City $city): RedirectResponse
    {
        $this->authorize('delete', $city);

        $name = $city->name;
        $city->delete();

        return back()->with('success', "{$name} wurde entfernt. Die Seiten sind nicht mehr erreichbar.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?City $existing = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:80'],
            // Drives the partner count shown on the page, so it is the code of
            // the city centre rather than any code in the area.
            'postal_code' => ['nullable', 'digits:5'],
            'headline' => ['nullable', 'string', 'max:200'],
            'intro' => ['nullable', 'string', 'max:2000'],
            // The part that cannot be templated, which is the part that earns
            // the ranking. HTML, written by the office.
            'body' => ['nullable', 'string', 'max:40000'],
            'faqs' => ['nullable', 'array', 'max:8'],
            'faqs.*.frage' => ['required_with:faqs.*.antwort', 'nullable', 'string', 'max:200'],
            'faqs.*.antwort' => ['required_with:faqs.*.frage', 'nullable', 'string', 'max:2000'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'is_active' => ['boolean'],
            'service_type_ids' => ['array'],
            'service_type_ids.*' => ['integer', 'exists:service_types,id'],
        ], [], [
            'name' => 'der Name',
            'postal_code' => 'die Postleitzahl',
            'meta_description' => 'die Meta-Beschreibung',
        ]);
    }
}
