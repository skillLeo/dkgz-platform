<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\ServiceType;
use App\Support\Settings;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        if (! Settings::bool('seo.sitemap_enabled', true)) {
            abort(404);
        }

        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('request.create'), 'priority' => '0.9'],
            ['loc' => route('services'), 'priority' => '0.8'],
            ['loc' => route('process'), 'priority' => '0.7'],
            ['loc' => route('about'), 'priority' => '0.6'],
            ['loc' => route('partner'), 'priority' => '0.7'],
            ['loc' => route('contact'), 'priority' => '0.6'],
        ]);

        ServiceType::active()->ordered()->each(function (ServiceType $type) use ($urls) {
            $urls->push(['loc' => route('services.show', $type), 'priority' => '0.7']);
        });

        Page::published()->each(function (Page $page) use ($urls) {
            $urls->push(['loc' => url("/{$page->slug}"), 'priority' => '0.3']);
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $directive = Settings::get('seo.robots', 'index, follow');
        $disallow = str_contains((string) $directive, 'noindex') ? '/' : '';

        $lines = [
            'User-agent: *',
            $disallow === '/' ? 'Disallow: /' : 'Disallow: /portal/',
            $disallow === '/' ? '' : 'Disallow: /admin/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", array_filter($lines, fn ($l) => $l !== null))."\n", 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
