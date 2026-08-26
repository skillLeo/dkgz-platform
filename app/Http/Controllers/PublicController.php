<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Post;
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
    /**
     * The Ratgeber index.
     *
     * Everything else on this site answers somebody who has already decided
     * they need an assessor. These articles are for the person a week earlier,
     * and the listing is arranged for skimming rather than reading — a title, a
     * date, how long it takes and the first two lines.
     */
    public function guide(): Response
    {
        $posts = Post::published()->ordered()->get();

        return Inertia::render('Public/Ratgeber', [
            'content' => Content::page('ratgeber'),
            'posts' => $posts->map(fn (Post $post) => $this->postCard($post))->values(),
            // Only the ones something is actually filed under, so the filter
            // never offers a heading that returns nothing.
            'categories' => $posts->pluck('category')->filter()->unique()->sort()->values(),
        ]);
    }

    /** One article. */
    public function guidePost(Post $post): Response
    {
        abort_unless($post->is_published && ! $post->date()?->isFuture(), 404);

        return Inertia::render('Public/RatgeberBeitrag', [
            'content' => Content::page('ratgeber'),
            'post' => $this->postCard($post) + [
                'body' => $post->body,
                'cover_alt' => $post->cover_alt,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'published_iso' => $post->date()?->toIso8601String(),
            ],
            // Something to read next, so an article is not a dead end.
            'more' => Post::published()->ordered()
                ->whereKeyNot($post->getKey())
                ->limit(3)
                ->get()
                ->map(fn (Post $other) => $this->postCard($other))
                ->values(),
        ]);
    }

    /** @return array<string, mixed> */
    private function postCard(Post $post): array
    {
        return [
            'slug' => $post->slug,
            'title' => $post->title,
            'category' => $post->category,
            'excerpt' => $post->summary(),
            'author' => $post->author,
            'cover_url' => $post->cover_path ? Storage::disk('public')->url($post->cover_path) : null,
            'published_at' => $post->date()?->translatedFormat('j. F Y'),
            'reading_minutes' => $post->readingMinutes(),
            'url' => "/ratgeber/{$post->slug}",
        ];
    }

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
