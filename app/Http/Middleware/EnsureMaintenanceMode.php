<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-toggled maintenance mode for the public site. Staff and the auth
 * screens stay reachable so the operator can turn it back off.
 */
class EnsureMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Settings::bool('features.maintenance_mode')) {
            return $next($request);
        }

        if ($request->user()?->isStaff()) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'message' => Settings::get(
                'features.maintenance_message',
                'Die Seite wird derzeit gewartet. Bitte versuchen Sie es in Kürze erneut.'
            ),
        ], 503);
    }
}
