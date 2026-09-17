<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ServiceType;
use App\Support\HeroPicture;
use App\Support\StoredImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

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
                    'image_url' => $city->imageUrl(),
                    'image' => StoredImage::meta($city->image_path),
                    'image_size' => $city->image_size,
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
            // Where the size slider starts for a picture with no size of its own.
            'defaultImageSize' => HeroPicture::defaultSize('staedte.stadt'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', City::class);

        // A new city has no picture yet, so nothing for a size to belong to.
        $city = City::create(array_merge($this->validated($request), ['image_size' => null]));
        $city->serviceTypes()->sync($request->input('service_type_ids', []));

        return back()->with('success', "{$city->name} wurde angelegt.");
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $this->authorize('update', $city);

        $data = $this->validated($request, $city);

        // A size belongs to a picture. A form opened before the picture was
        // removed still carries the old size, and saving it would hand that
        // size to whatever is uploaded next.
        if ($city->image_path === null) {
            $data['image_size'] = null;
        }

        $city->update($data);
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

    /**
     * The picture beside the headline on this city's page, and on its service
     * pages where the service has no picture of its own.
     */
    public function uploadImage(Request $request, City $city): RedirectResponse
    {
        $this->authorize('update', $city);

        $request->validate(['image' => StoredImage::RULES], [], ['image' => 'das Bild']);

        $previous = $city->image_path;

        try {
            $path = StoredImage::store($request->file('image'), 'staedte');
        } catch (RuntimeException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }

        $city->update(['image_path' => $path]);

        StoredImage::forget($previous);

        return back()->with('success', 'Das Bild wurde gespeichert.');
    }

    public function destroyImage(Request $request, City $city): RedirectResponse
    {
        $this->authorize('update', $city);

        // The size went with the picture it was set for.
        StoredImage::forget($city->image_path);
        $city->update(['image_path' => null, 'image_size' => null]);

        return back()->with('success', 'Das Bild wurde entfernt. Die Seiten zeigen wieder das Standardbild.');
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
            // The size of this city's own picture. Empty follows the page.
            'image_size' => ['nullable', 'integer', 'between:'.HeroPicture::MIN_SIZE.','.HeroPicture::MAX_SIZE],
        ], [], [
            'name' => 'der Name',
            'postal_code' => 'die Postleitzahl',
            'meta_description' => 'die Meta-Beschreibung',
            'image_size' => 'die Bildgröße',
        ]);
    }
}
