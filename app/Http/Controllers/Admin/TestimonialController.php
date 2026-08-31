<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Support\ImagePipeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Customer quotes for the homepage.
 *
 * Real people only — a fabricated review is illegal in Germany under the UWG,
 * and it is the one thing on the page that stops working the moment it stops
 * being true. Nothing here invents anything: it stores what somebody actually
 * said, with the name they agreed to.
 */
class TestimonialController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('content.view');

        return Inertia::render('Admin/Kundenstimmen', [
            'testimonials' => Testimonial::ordered()->get()->map(fn (Testimonial $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'location' => $t->location,
                'quote' => $t->quote,
                'rating' => $t->rating,
                'photo_url' => $t->photoUrl(),
                'initials' => $t->initials(),
                'is_published' => $t->is_published,
            ]),
            'canEdit' => $request->user()->can('content.edit'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('content.edit');

        $testimonial = Testimonial::create($this->validated($request) + [
            'sort_order' => (int) Testimonial::max('sort_order') + 1,
        ]);

        $this->storePhoto($request, $testimonial);

        return back()->with('success', 'Die Kundenstimme wurde angelegt.');
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('content.edit');

        $testimonial->update($this->validated($request));
        $this->storePhoto($request, $testimonial);

        return back()->with('success', 'Gespeichert.');
    }

    public function destroy(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('content.edit');

        $this->forgetPhoto($testimonial->photo_path);
        $testimonial->delete();

        return back()->with('success', 'Die Kundenstimme wurde gelöscht.');
    }

    /**
     * Re-encoded rather than stored as uploaded: sRGB so the face on the page
     * looks like the one in the file, the phone's rotation applied, the size
     * bounded, and the EXIF block dropped — a customer's photograph should not
     * publish where it was taken.
     */
    private function storePhoto(Request $request, Testimonial $testimonial): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        try {
            $binary = ImagePipeline::encode($request->file('photo'));
        } catch (RuntimeException) {
            return;
        }

        $previous = $testimonial->photo_path;
        $path = 'kundenstimmen/'.bin2hex(random_bytes(12)).'.webp';

        Storage::disk('public')->put($path, $binary);
        $testimonial->update(['photo_path' => $path]);

        $this->forgetPhoto($previous);
    }

    private function forgetPhoto(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'quote' => ['required', 'string', 'max:600'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_published' => ['boolean'],
            'photo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:12288'],
        ], [], [
            'name' => 'der Name',
            'quote' => 'das Zitat',
            'photo' => 'das Foto',
        ]);

        unset($data['photo']);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
