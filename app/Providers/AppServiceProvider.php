<?php

namespace App\Providers;

use App\Listeners\RecordMailDelivery;
use App\Models\ContentBlock;
use App\Models\SeoSetting;
use App\Models\Setting;
use App\Observers\CacheBustingObserver;
use App\Support\Branding;
use App\Support\SafeStorage;
use App\Support\Settings;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(MessageSent::class, RecordMailDelivery::class);

        Date::use(Carbon::class);

        Vite::prefetch(concurrency: 3);

        Setting::observe(CacheBustingObserver::class);
        ContentBlock::observe(CacheBustingObserver::class);

        // The root document reached for both of these and nothing ever supplied
        // them, so an uploaded favicon and every colour set under
        // "Erscheinungsbild" were saved, shown back in the form, and had no
        // effect on the site whatsoever.
        View::composer('app', function ($view) {
            // Whether this particular page may be indexed. Server-side, because
            // a crawler that does not run the JavaScript still has to be told.
            $view->with('isIndexed', SeoSetting::indexes(
                '/'.ltrim(request()->path() === '/' ? '' : request()->path(), '/')
            ));

            $view->with([
                'faviconUrl' => SafeStorage::url(Settings::get('branding.favicon')),
                'brandCss' => Branding::cssCustomProperties(),
                // Search Console's ownership check. A static token: it sets no
                // cookie and reports nothing, so it needs no consent and goes
                // in the document rather than behind the banner.
                'siteVerification' => Settings::get('integrations.google_site_verification'),
            ]);
        });

        $this->registerRateLimiters();

        /*
         * Where somebody who is already signed in goes if they ask for the
         * login screen.
         *
         * Laravel's default is the site root, and the public homepage says
         * nothing about being signed in — so a partner whose session was still
         * alive clicked "Partner-Portal", landed back on the homepage, and
         * concluded that logging in was broken. Sending them to their own area
         * both answers the request and makes the state visible.
         */
        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            $user = $request->user();

            if ($user === null) {
                return '/';
            }

            if ($user->can('admin.access') || $user->hasAnyRole(['admin', 'super_admin'])) {
                return route('admin.dashboard');
            }

            return $user->assessor !== null ? route('portal.dashboard') : '/';
        });
    }

    /**
     * Limits from BUILD_SPEC Part D. Login is keyed on IP *and* e-mail so one
     * noisy address cannot lock out everyone behind the same NAT.
     */
    private function registerRateLimiters(): void
    {
        // Coarse abuse guard only. The 5/min per IP+e-mail rule that produces
        // the German "Zu viele Anmeldeversuche" message lives in LoginRequest,
        // so a throttled user gets a readable form error rather than a bare 429.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('anfrage', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('passwort-reset', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('kontakt', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        // Polling is deliberately generous: the portal hits it every 45 seconds
        // and a user may have several tabs open.
        // Accepting is a race between partners; a script hammering it would
        // distort that race and hold locks the rest of the network waits on.
        RateLimiter::for('vermittlung', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('polling', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('smtp-test', fn (Request $request) => Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()));
    }
}
