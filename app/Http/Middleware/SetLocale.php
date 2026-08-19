<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The interface is German throughout. This pins the locale so validation
 * messages and date formatting never fall back to English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($request->user()?->locale ?? config('app.locale', 'de'));

        return $next($request);
    }
}
