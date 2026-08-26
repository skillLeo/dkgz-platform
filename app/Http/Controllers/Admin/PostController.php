<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\ImagePipeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * The Ratgeber, from the office's side.
 *
 * Writing an article should be typing a title and a body and ticking a box.
 * Everything a search engine wants — the address, the description, the date —
 * is derived unless somebody deliberately says otherwise, because a form that
 * demands eight fields before anything can be published is a form nobody
 * publishes from.
 */
class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Post::class);

        return Inertia::render('Admin/Ratgeber', [
            'posts' => Post::ordered()->get()->map(fn (Post $post) => [
                'id' => $post->id,
                'slug' => $post->slug,
                'title' => $post->title,
                'category' => $post->category,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'cover_url' => $post->cover_path ? Storage::disk('public')->url($post->cover_path) : null,
                'cover_alt' => $post->cover_alt,
                'author' => $post->author,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'is_published' => $post->is_published,
                'published_at' => $post->published_at?->format('Y-m-d'),
                // Ticked but dated ahead: live to nobody yet, and the list has
                // to say so or it looks broken.
                'is_scheduled' => $post->is_published && $post->published_at?->isFuture(),
                'reading_minutes' => $post->readingMinutes(),
                'url' => "/ratgeber/{$post->slug}",
            ]),
            'categories' => Post::CATEGORIES,
            'canEdit' => $request->user()->can('posts.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $post = Post::create($this->validated($request));
        $this->storeCover($request, $post);

        return back()->with('success', "„{$post->title}“ wurde angelegt.");
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $post->update($this->validated($request));
        $this->storeCover($request, $post);

        return back()->with('success', "„{$post->title}“ wurde gespeichert.");
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $title = $post->title;
        $this->forgetCover($post->cover_path);
        $post->delete();

        return back()->with('success', "„{$title}“ wurde gelöscht.");
    }

    /** Removing the picture without removing the article. */
    public function destroyCover(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $this->forgetCover($post->cover_path);
        $post->update(['cover_path' => null]);

        return back()->with('success', 'Das Bild wurde entfernt.');
    }

    /**
     * Re-encoded rather than stored as uploaded: sRGB so the picture on the
     * page looks like the one the operator chose, the phone's rotation flag
     * applied, the size bounded, and the EXIF block dropped along with its GPS
     * coordinates — an article photograph should not publish where it was taken.
     */
    private function storeCover(Request $request, Post $post): void
    {
        if (! $request->hasFile('cover')) {
            return;
        }

        try {
            $binary = ImagePipeline::encode($request->file('cover'));
        } catch (RuntimeException $e) {
            return;
        }

        $previous = $post->cover_path;
        $path = 'ratgeber/'.bin2hex(random_bytes(12)).'.webp';

        Storage::disk('public')->put($path, $binary);
        $post->update(['cover_path' => $path]);

        // Replacing a picture deletes the one it replaced. Without this every
        // correction leaves a file nobody can reach and nobody will ever clean.
        $this->forgetCover($previous);
    }

    private function forgetCover(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['nullable', Rule::in(Post::CATEGORIES)],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:60000'],
            'cover_alt' => ['nullable', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:120'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:12288'],
        ], [], [
            'title' => 'der Titel',
            'body' => 'der Text',
            'cover' => 'das Bild',
        ]);

        unset($data['cover']);

        // Publishing without a date means publishing now, which is what ticking
        // the box plainly meant.
        if (($data['is_published'] ?? false) && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
