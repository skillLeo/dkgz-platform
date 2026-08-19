<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    public function show(string $slug): Response
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Public/Rechtliches', [
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title_de,
                'body' => $page->body_de,
                'updated_at' => $page->updated_at,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ],
            'navigation' => Page::published()->orderBy('sort_order')->get(['slug', 'title_de'])
                ->map(fn (Page $p) => ['slug' => $p->slug, 'title' => $p->title_de]),
        ]);
    }
}
