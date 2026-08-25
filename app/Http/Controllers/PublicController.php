<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\PostalCode;
use App\Models\ServiceType;
use App\Support\Content;
use App\Support\CoverageMap;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Public/Startseite', [
            'coverage' => CoverageMap::regions(),
            'content' => Content::page('startseite'),
            'serviceTypes' => $this->activeServiceTypes(),
            'faqs' => Faq::published()->onHomepage()->ordered()->get(['id', 'question_de', 'answer_de']),
        ]);
    }

    public function services(): Response
    {
        return Inertia::render('Public/Leistungen', [
            'content' => Content::page('leistungen'),
            'serviceTypes' => $this->activeServiceTypes(),
        ]);
    }

    public function service(ServiceType $serviceType): Response
    {
        abort_unless($serviceType->is_active, 404);

        return Inertia::render('Public/Leistung', [
            'content' => Content::page('leistungen'),
            // The gender travels with the name so the editable copy can bend
            // its articles to it — "zum Unfallgutachten", "zur Beweissicherung".
            'serviceType' => $serviceType->only([
                'id', 'slug', 'name_de', 'description_de', 'icon',
                'includes_de', 'target_audience_de', 'typical_situations_de',
                'differences_de', 'additional_info_de', 'faqs',
            ]) + ['genus' => $serviceType->genus()],
            'serviceTypes' => $this->activeServiceTypes(),
        ]);
    }

    /**
     * Every published question, grouped by the category it was filed under.
     *
     * The homepage shows a handful; this is the whole set, and it is what the
     * header points at now that "Für Sachverständige" has moved to the footer
     * — a visitor with a question is far commoner than one looking to join.
     */
    public function faq(): Response
    {
        return Inertia::render('Public/Faq', [
            'content' => Content::page('faq'),
            'groups' => Faq::published()->ordered()
                ->get(['id', 'question_de', 'answer_de', 'category'])
                ->groupBy(fn (Faq $faq) => $faq->category ?: 'Allgemein'),
        ]);
    }

    public function process(): Response
    {
        return Inertia::render('Public/Ablauf', [
            'content' => Content::page('ablauf'),
            'homeContent' => Content::page('startseite'),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('Public/UeberUns', [
            'content' => Content::page('ueber-uns'),
        ]);
    }

    public function partner(): Response
    {
        return Inertia::render('Public/FuerSachverstaendige', [
            'content' => Content::page('partner'),
            'registrationOpen' => Settings::bool('features.self_registration', true),
        ]);
    }

    /**
     * Backs the postal-code field's city lookup.
     *
     * A code the seeded table does not know is not an error — the table covers
     * a fraction of Germany's codes and exists only to save typing. It answers
     * 200 with a null city so the field simply does not auto-fill, rather than
     * a 404 the form might treat as a rejection.
     */
    public function resolvePostalCode(string $code): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'city' => PostalCode::cityFor($code),
            'known' => PostalCode::exists($code),
        ]);
    }

    /**
     * Serves a public asset when `storage:link` did not take on the host.
     * Confined to the public disk so a traversal cannot reach anything else.
     */
    public function media(string $path): StreamedResponse
    {
        abort_if(str_contains($path, '..'), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    private function activeServiceTypes()
    {
        return ServiceType::active()->ordered()->get(['id', 'slug', 'name_de', 'description_de', 'icon']);
    }
}
