<?php

namespace App\Http\Middleware;

use App\Models\FunnelEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * How many people came to the site at all.
 *
 * The funnel began at "opened the request form", which answers how well the
 * form converts but not how well the site does — a quiet week and a week where
 * nobody found the form look identical from inside it.
 *
 * Counted once per session, so this is visits rather than page views: a person
 * reading four pages is one visitor, which is the number that was actually
 * asked for. Still no identifier of any kind and nothing written to the
 * visitor's machine beyond the session cookie the site already sets, so it
 * needs no consent and counts everybody rather than only those who accepted a
 * banner.
 */
class CountVisitor
{
    /** The session key holding "this visit has been counted". */
    public const SESSION_KEY = 'dkgz.counted';

    /** Everything behind a login. A staff member is not a visitor. */
    private const PRIVATE_PREFIXES = ['admin', 'portal', 'anmelden', 'registrieren', 'passwort', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        $this->count($request);

        return $next($request);
    }

    private function count(Request $request): void
    {
        if (! $this->countable($request)) {
            return;
        }

        try {
            $request->session()->put(self::SESSION_KEY, true);

            FunnelEvent::record('besucher');
        } catch (Throwable) {
            // A counter must never be the reason a page fails to render.
        }
    }

    private function countable(Request $request): bool
    {
        if ($request->method() !== 'GET' || ! $request->hasSession()) {
            return false;
        }

        if ($request->session()->get(self::SESSION_KEY) === true) {
            return false;
        }

        foreach (self::PRIVATE_PREFIXES as $prefix) {
            if ($request->is($prefix, "{$prefix}/*")) {
                return false;
            }
        }

        return ! $this->looksAutomated($request);
    }

    /**
     * Obvious crawlers, left out.
     *
     * Not a defence — anything can claim to be a browser — but the well-behaved
     * crawlers announce themselves, and they are numerous enough that leaving
     * them in would make the visitor count read high for no useful reason.
     */
    private function looksAutomated(Request $request): bool
    {
        $agent = strtolower($request->userAgent() ?? '');

        if ($agent === '') {
            return true;
        }

        foreach (['bot', 'crawler', 'spider', 'slurp', 'curl', 'wget', 'headless', 'preview', 'monitor'] as $marker) {
            if (str_contains($agent, $marker)) {
                return true;
            }
        }

        return false;
    }
}
