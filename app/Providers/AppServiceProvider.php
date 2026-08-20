<?php

namespace App\Providers;

use App\Listeners\RecordMailDelivery;
use App\Models\ContentBlock;
use App\Models\Setting;
use App\Observers\CacheBustingObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->registerRateLimiters();
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
