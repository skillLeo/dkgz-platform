<?php

namespace App\Http\Controllers;

use App\Models\Assessor;
use App\Models\City;
use App\Models\Page;
use App\Models\Post;
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
            ['loc' => route('cities'), 'priority' => '0.7'],
            ['loc' => route('guide'), 'priority' => '0.6'],
            ['loc' => route('directory'), 'priority' => '0.6'],
        ]);

        // One page per listed partner: the only part of the site that names the
        // firms themselves, and the reason the directory is worth having.
        Assessor::listed()->orderBy('company_name')->each(function (Assessor $assessor) use ($urls) {
            $urls->push(['loc' => url("/sachverstaendige/{$assessor->slug}"), 'priority' => '0.4']);
        });

        // Articles. These exist to be found by somebody who has not yet decided
        // they need an assessor, so listing them is most of the point.
        Post::published()->ordered()->each(function (Post $post) use ($urls) {
            $urls->push(['loc' => url("/ratgeber/{$post->slug}"), 'priority' => '0.5']);
        });

        ServiceType::active()->ordered()->each(function (ServiceType $type) use ($urls) {
            $urls->push(['loc' => route('services.show', $type), 'priority' => '0.7']);
        });

        // City pages: the hub, then each service offered there. These exist to
        // be found, so listing them is most of the point of having them.
        City::active()->ordered()->with('publishedServiceTypes')->each(function (City $city) use ($urls) {
            if ($city->publishedServiceTypes->isEmpty()) {
                return;
            }

            $urls->push(['loc' => url("/kfz-gutachter/{$city->slug}"), 'priority' => '0.7']);

            foreach ($city->publishedServiceTypes as $type) {
                $urls->push([
                    'loc' => url("/kfz-gutachter/{$city->slug}/{$type->slug}"),
                    'priority' => '0.6',
                ]);
            }
        });

        Page::published()->each(function (Page $page) use ($urls) {
            $urls->push(['loc' => url("/{$page->slug}"), 'priority' => '0.3']);
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * robots.txt, generated rather than a static file.
     *
     * There was a static public/robots.txt as well, and the web server answered
     * with that one — so this method's careful disallow list and its pointer to
     * the sitemap never reached a single crawler. The file is gone; this is the
     * only robots.txt now, which also means it names the right domain on the
     * live site and the test site rather than advertising the other's sitemap.
     */
    public function robots(): Response
    {
        $directive = Settings::get('seo.robots', 'index, follow');

        if (str_contains((string) $directive, 'noindex')) {
            return $this->plain(['User-agent: *', 'Disallow: /']);
        }

        $lines = ['User-agent: *'];

        // Private, meant for one visitor, or simply of no use in a result list.
        foreach ([
            '/admin/',
            '/portal/',
            '/anmelden',
            '/registrieren',
            '/passwort',
            '/bewertung/',
            '/auftrag-angebot/',
            '/einladung/',
            '/anfrage/bestaetigung/',
        ] as $path) {
            $lines[] = "Disallow: {$path}";
        }

        if (Settings::bool('seo.sitemap_enabled', true)) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.route('sitemap');
        }

        return $this->plain($lines);
    }

    /** @param  list<string>  $lines */
    private function plain(array $lines): Response
    {
        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
