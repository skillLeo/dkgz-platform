<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ContentBlock;
use App\Models\Post;
use App\Models\SeoSetting;
use App\Models\ServiceType;
use App\Support\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every public page, and what a search engine will make of it.
 *
 * The titles and descriptions already exist — in a content block, in a city's
 * own column, in an article's. This screen does not copy them: it reads each
 * one from wherever it actually lives and writes an edit straight back there,
 * so there is never a second version to disagree with the first. The only thing
 * stored centrally is whether a page may be indexed, which has nowhere else to
 * live.
 *
 * What it adds over editing those places one at a time is the view: a hundred
 * and fifty pages in one list, with the ones missing a description or running
 * past what Google will show marked as such.
 */
class SeoController extends Controller
{
    /** Google truncates a title near here and a description near here. */
    private const TITLE_LIMIT = 60;

    private const DESCRIPTION_LIMIT = 155;

    public function index(Request $request): Response
    {
        $this->authorize('content.view');

        $rows = collect()
            ->merge($this->staticPages())
            ->merge($this->servicePages())
            ->merge($this->cityPages())
            ->merge($this->postPages())
            ->map(fn (array $row) => $row + [
                'indexed' => SeoSetting::indexes($row['path']),
                'issues' => $this->issues($row),
            ])
            ->values();

        return Inertia::render('Admin/Seo', [
            'pages' => $rows,
            'limits' => ['title' => self::TITLE_LIMIT, 'description' => self::DESCRIPTION_LIMIT],
            'canEdit' => $request->user()->can('content.edit'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('content.edit');

        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', 'in:content,city,post,none'],
            'reference' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:400'],
            'indexed' => ['boolean'],
        ], [], ['title' => 'der Seitentitel', 'description' => 'die Beschreibung']);

        // Written back where the string actually lives, so the SEO screen and
        // the page's own editor can never disagree about what it says.
        match ($data['source']) {
            'content' => $this->writeContent($data),
            'city' => $this->writeCity($data),
            'post' => $this->writePost($data),
            default => null,
        };

        SeoSetting::set($data['path'], $request->boolean('indexed'));

        return back()->with('success', 'Gespeichert.');
    }

    /** @param array<string, mixed> $data */
    private function writeContent(array $data): void
    {
        [$page, $section] = explode('.', (string) $data['reference']);

        // Created where it does not exist yet: several pages had no meta
        // block at all, which is exactly the gap this screen is for — and an
        // update alone would have silently done nothing about it.
        foreach ([
            'meta_titel' => ['text', 'Seitentitel für Google', $data['title']],
            'meta_text' => ['richtext', 'Beschreibung für Google', $data['description']],
        ] as $field => [$type, $label, $value]) {
            ContentBlock::updateOrCreate(
                ['page_key' => $page, 'section_key' => $section, 'field_key' => $field],
                ['value' => $value, 'type' => $type, 'label_de' => $label],
            );
        }

        Content::flush($page);
    }

    /** @param array<string, mixed> $data */
    private function writeCity(array $data): void
    {
        City::whereKey($data['reference'])->update([
            'meta_title' => $data['title'],
            'meta_description' => $data['description'],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function writePost(array $data): void
    {
        Post::whereKey($data['reference'])->update([
            'meta_title' => $data['title'],
            'meta_description' => $data['description'],
        ]);
    }

    /**
     * What is wrong with this page, in the order somebody would fix it.
     *
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function issues(array $row): array
    {
        $issues = [];
        $title = trim((string) $row['title']);
        $description = trim((string) $row['description']);

        if ($title === '') {
            $issues[] = 'Kein Seitentitel';
        } elseif (mb_strlen($title) > self::TITLE_LIMIT) {
            $issues[] = 'Titel zu lang';
        }

        if ($description === '') {
            $issues[] = 'Keine Beschreibung';
        } elseif (mb_strlen($description) > self::DESCRIPTION_LIMIT) {
            $issues[] = 'Beschreibung zu lang';
        }

        return $issues;
    }

    /**
     * The pages whose wording lives in content blocks.
     *
     * @return list<array<string, mixed>>
     */
    private function staticPages(): array
    {
        $pages = [
            ['/', 'startseite', 'kopf', 'Startseite'],
            ['/leistungen', 'leistungen', 'kopf', 'Leistungen'],
            ['/ablauf', 'ablauf', 'kopf', 'Ablauf'],
            ['/ueber-uns', 'ueber-uns', 'kopf', 'Über uns'],
            ['/kontakt', 'kontakt', 'kopf', 'Kontakt'],
            ['/faq', 'faq', 'kopf', 'FAQ'],
            ['/ratgeber', 'ratgeber', 'kopf', 'Ratgeber'],
            ['/sachverstaendige', 'verzeichnis', 'kopf', 'Sachverständige'],
            ['/fuer-sachverstaendige', 'partner', 'kopf', 'Für Sachverständige'],
            ['/anfrage', 'anfrage', 'kopf', 'Anfrageformular'],
        ];

        return collect($pages)->map(function (array $page) {
            [$path, $key, $section, $label] = $page;
            $content = Content::page($key);

            return [
                'path' => $path,
                'label' => $label,
                'group' => 'Seiten',
                'source' => 'content',
                'reference' => "{$key}.{$section}",
                'title' => $content[$section]['meta_titel'] ?? '',
                'description' => $content[$section]['meta_text'] ?? '',
                'editable' => isset($content[$section]['meta_titel']),
            ];
        })->all();
    }

    /** @return list<array<string, mixed>> */
    private function servicePages(): array
    {
        $content = Content::page('leistungen');

        return ServiceType::active()->ordered()->get(['slug', 'name_de'])
            ->map(fn (ServiceType $type) => [
                'path' => "/leistungen/{$type->slug}",
                'label' => $type->name_de,
                'group' => 'Leistungen',
                // One template serves them all, so editing here would change
                // every service page at once — which is right, and says so.
                'source' => 'content',
                'reference' => 'leistungen.detail',
                'title' => $content['detail']['meta_titel'] ?? '',
                'description' => $content['detail']['meta_text'] ?? '',
                'editable' => true,
                'shared' => true,
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function cityPages(): array
    {
        $content = Content::page('staedte');

        return City::active()->ordered()->get()
            ->map(fn (City $city) => [
                'path' => "/kfz-gutachter/{$city->slug}",
                'label' => $city->name,
                'group' => 'Städte',
                'source' => 'city',
                'reference' => (string) $city->id,
                'title' => $city->meta_title ?: ($content['stadt']['meta_titel'] ?? ''),
                'description' => $city->meta_description ?: ($content['stadt']['meta_text'] ?? ''),
                'editable' => true,
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function postPages(): array
    {
        return Post::published()->ordered()->get()
            ->map(fn (Post $post) => [
                'path' => "/ratgeber/{$post->slug}",
                'label' => $post->title,
                'group' => 'Ratgeber',
                'source' => 'post',
                'reference' => (string) $post->id,
                'title' => $post->meta_title ?: $post->title,
                'description' => $post->meta_description ?: $post->summary(),
                'editable' => true,
            ])->all();
    }
}
