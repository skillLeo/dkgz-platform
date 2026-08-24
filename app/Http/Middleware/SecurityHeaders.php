<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\Settings;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /** Routes the application frames itself; everything else stays DENY. */
    private const FRAMEABLE = ['admin.emails.preview'];


    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // The mail preview is deliberately shown inside a frame on the template
        // editor, and a blanket DENY made that frame render blank — the editor
        // looked broken while the preview itself was fine. Only this one route
        // is allowed to be framed, and only by us.
        $framedBySelf = $request->routeIs(self::FRAMEABLE);

        $response->headers->set('X-Frame-Options', $framedBySelf ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'
        );

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', $this->policy($framedBySelf));

        return $response;
    }

    /**
     * No external origins at all. Fonts are self-hosted, so font-src stays
     * 'self' — any CDN font request would be both a CSP violation and, under
     * German case law, a GDPR one.
     */
    private function policy(bool $framedBySelf = false): string
    {
        // Google Analytics, and only where the operator has configured it. The
        // policy is otherwise "nothing but us": adding these hosts
        // unconditionally would leave the door open on an installation that
        // does no tracking at all.
        $analytics = filled(Settings::get('integrations.analytics_id'))
            ? ' https://www.googletagmanager.com'
            : '';
        $analyticsConnect = $analytics === ''
            ? ''
            : ' https://www.googletagmanager.com https://www.google-analytics.com https://region1.google-analytics.com';

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            $framedBySelf ? "frame-ancestors 'self'" : "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: blob:".($analytics === '' ? '' : ' https://www.google-analytics.com https://www.googletagmanager.com'),
            "font-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "connect-src 'self'".$analyticsConnect,
        ];

        // Vite's dev client needs eval and its own websocket; production does not.
        $directives[] = app()->environment('local')
            ? "script-src 'self' 'unsafe-inline' 'unsafe-eval'".$analytics
            : "script-src 'self'".$analytics;

        if (app()->environment('local')) {
            $directives[] = "connect-src 'self' ws: wss: http://localhost:* http://127.0.0.1:*";
        }

        return implode('; ', $directives);
    }
}
