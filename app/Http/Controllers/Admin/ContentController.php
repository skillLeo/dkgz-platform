<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Page;
use App\Support\Content;
use App\Support\Formatter;
use App\Support\SafeStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function index(Request $request, ?string $pageKey = null): Response
    {
        $this->authorize('viewAny', ContentBlock::class);

        $pages = Content::pageKeys();
        $pageKey ??= array_key_first($pages);

        abort_unless(array_key_exists($pageKey, $pages), 404);

        // Grouped by section so the editor mirrors the visual order of the page.
        $blocks = ContentBlock::forPage($pageKey)->get()
            ->groupBy('section_key')
            ->map(fn ($group) => $group->map(fn (ContentBlock $b) => [
                'id' => $b->id,
                'field_key' => $b->field_key,
                'label' => $b->label_de,
                'type' => $b->type,
                'value' => $b->value,
                'preview_url' => $b->type === 'image' ? SafeStorage::url($b->value) : null,
                'image' => $b->type === 'image' ? $this->imageMeta($b) : null,
                'help' => $b->help_de,
            ])->values());

        return Inertia::render('Admin/Inhalte', [
            'pageKey' => $pageKey,
            'pages' => $pages,
            'sections' => $blocks,
            'canEdit' => $request->user()->can('content.edit'),
        ]);
    }

    public function update(Request $request, string $pageKey): RedirectResponse
    {
        $this->authorize('content.edit');

        $data = $request->validate([
            'blocks' => ['required', 'array'],
            'blocks.*.id' => ['required', 'integer'],
            'blocks.*.value' => ['nullable', 'string', 'max:20000'],
        ]);

        $ids = collect($data['blocks'])->pluck('id');
        $blocks = ContentBlock::whereIn('id', $ids)->where('page_key', $pageKey)->get()->keyBy('id');

        foreach ($data['blocks'] as $submitted) {
            $block = $blocks->get($submitted['id']);

            // An image block holds a path written by the upload endpoint, and
            // the form still carries whatever path it was rendered with. Saving
            // any text on the page therefore wrote the old picture back over
            // the new one — the upload worked and then undid itself. Pictures
            // are changed by uploading or removing them, never by this form.
            if ($block === null || $block->type === 'image') {
                continue;
            }

            $block->update(['value' => $submitted['value']]);
        }

        Content::flush($pageKey);

        return back()->with('success', 'Die Inhalte wurden gespeichert.');
    }

    public function uploadImage(Request $request, ContentBlock $contentBlock): RedirectResponse
    {
        $this->authorize('content.edit');
        abort_unless($contentBlock->type === 'image', 422);

        $request->validate(
            ['image' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:4096']],
            [],
            ['image' => 'das Bild']
        );

        $previous = $contentBlock->value;

        $contentBlock->update(['value' => $request->file('image')->store('inhalte', 'public')]);

        // Replacing an image deletes the one it replaced. Without this every
        // correction leaves a file nobody can reach and nobody will ever clean.
        $this->forgetFile($previous);

        Content::flush($contentBlock->page_key);

        return back()->with('success', 'Das Bild wurde gespeichert.');
    }

    public function destroyImage(Request $request, ContentBlock $contentBlock): RedirectResponse
    {
        $this->authorize('content.edit');
        abort_unless($contentBlock->type === 'image', 422);

        $this->forgetFile($contentBlock->value);
        $contentBlock->update(['value' => '']);

        Content::flush($contentBlock->page_key);

        return back()->with('success', 'Das Bild wurde entfernt. Die Seite zeigt wieder den Platzhalter.');
    }

    /**
     * Size and dimensions of the stored file, so the editor can see what is
     * actually there rather than only that something is.
     *
     * @return array<string, mixed>|null
     */
    private function imageMeta(ContentBlock $block): ?array
    {
        if (blank($block->value) || ! Storage::disk('public')->exists($block->value)) {
            return null;
        }

        $meta = [
            'name' => basename($block->value),
            'size_label' => Formatter::fileSize(Storage::disk('public')->size($block->value)),
            'dimensions' => null,
        ];

        $dimensions = @getimagesize(Storage::disk('public')->path($block->value));

        if ($dimensions !== false) {
            $meta['dimensions'] = $dimensions[0].' × '.$dimensions[1].' px';
        }

        return $meta;
    }

    /** Removes a stored file, tolerating one that has already gone. */
    private function forgetFile(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    // ---- Legal and standalone pages -------------------------------------

    public function pages(Request $request): Response
    {
        $this->authorize('viewAny', Page::class);

        return Inertia::render('Admin/Seiten', [
            'pages' => Page::orderBy('sort_order')->get()
                ->map(fn (Page $p) => [
                    'id' => $p->id,
                    'slug' => $p->slug,
                    'title' => $p->title_de,
                    'is_published' => $p->is_published,
                    'updated_at' => $p->updated_at,
                ]),
        ]);
    }

    public function editPage(Request $request, Page $page): Response
    {
        $this->authorize('update', $page);

        return Inertia::render('Admin/Seite', [
            'isPlaceholder' => $page->is_placeholder,
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title_de' => $page->title_de,
                'body_de' => $page->body_de,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'is_published' => $page->is_published,
            ],
        ]);
    }

    public function updatePage(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);

        $data = $request->validate([
            'title_de' => ['required', 'string', 'max:180'],
            'body_de' => ['required', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:400'],
            'is_published' => ['boolean'],
        ], [], [
            'title_de' => 'der Titel',
            'body_de' => 'der Inhalt',
        ]);

        $page->update(array_merge($data, ['is_placeholder' => false]));

        return back()->with('success', 'Die Seite wurde gespeichert.');
    }

    // ---- FAQ -------------------------------------------------------------

    public function faqs(Request $request): Response
    {
        $this->authorize('viewAny', Faq::class);

        return Inertia::render('Admin/Faq', [
            'faqs' => Faq::ordered()->get()
                ->map(fn (Faq $f) => [
                    'id' => $f->id,
                    'question_de' => $f->question_de,
                    'answer_de' => $f->answer_de,
                    'category' => $f->category,
                    'sort_order' => $f->sort_order,
                    'is_published' => $f->is_published,
                ]),
        ]);
    }

    public function storeFaq(Request $request): RedirectResponse
    {
        $this->authorize('create', Faq::class);

        Faq::create($this->faqRules($request) + [
            'sort_order' => (int) Faq::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Die Frage wurde angelegt.');
    }

    public function updateFaq(Request $request, Faq $faq): RedirectResponse
    {
        $this->authorize('update', $faq);

        $faq->update($this->faqRules($request));

        return back()->with('success', 'Die Frage wurde gespeichert.');
    }

    public function destroyFaq(Request $request, Faq $faq): RedirectResponse
    {
        $this->authorize('delete', $faq);

        $faq->delete();

        return back()->with('success', 'Die Frage wurde gelöscht.');
    }

    public function reorderFaqs(Request $request): RedirectResponse
    {
        $this->authorize('reorder', Faq::class);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $position => $id) {
            Faq::whereKey($id)->update(['sort_order' => $position + 1]);
        }

        return back()->with('success', 'Die Reihenfolge wurde gespeichert.');
    }

    /** @return array<string, mixed> */
    private function faqRules(Request $request): array
    {
        return $request->validate([
            'question_de' => ['required', 'string', 'max:400'],
            'answer_de' => ['required', 'string', 'max:8000'],
            'category' => ['nullable', 'string', 'max:80'],
            'is_published' => ['boolean'],
        ], [], [
            'question_de' => 'die Frage',
            'answer_de' => 'die Antwort',
        ]);
    }
}
