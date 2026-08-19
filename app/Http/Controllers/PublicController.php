<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\PostalCode;
use App\Models\ServiceType;
use App\Support\Content;
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
            'content' => Content::page('startseite'),
            'serviceTypes' => $this->activeServiceTypes(),
            'faqs' => Faq::published()->ordered()->get(['id', 'question_de', 'answer_de']),
        ]);
    }

    public function services(): Response
    {
        return Inertia::render('Public/Leistungen', [
            'content' => Content::page('startseite'),
            'serviceTypes' => $this->activeServiceTypes(),
        ]);
    }

    public function service(ServiceType $serviceType): Response
    {
        abort_unless($serviceType->is_active, 404);

        return Inertia::render('Public/Leistung', [
            'serviceType' => $serviceType->only(['id', 'slug', 'name_de', 'description_de', 'icon']),
            'serviceTypes' => $this->activeServiceTypes(),
            'faqs' => Faq::published()->ordered()->limit(4)->get(['id', 'question_de', 'answer_de']),
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
            'commissionRate' => Settings::commissionRate(),
            'registrationOpen' => Settings::bool('features.self_registration', true),
        ]);
    }

    /** Backs the postal-code field's city lookup. */
    public function resolvePostalCode(string $code): JsonResponse
    {
        $city = PostalCode::cityFor($code);

        if ($city === null) {
            return response()->json(['message' => 'Diese Postleitzahl kennen wir nicht.'], 404);
        }

        return response()->json(['code' => $code, 'city' => $city]);
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
