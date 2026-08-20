<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds a user on the password screen until they replace a password somebody
 * else chose for them.
 *
 * The seeded administrator's password is printed to a console and may sit in a
 * deployment log or a chat message; it is a shared secret until its owner
 * changes it.
 */
class RequirePasswordChange
{
    /** Reachable while the change is outstanding, or the user would be trapped. */
    private const ALLOWED = [
        'portal/einstellungen',
        'portal/einstellungen/passwort',
        'admin/einstellungen/passwort',
        'admin/profil',
        'abmelden',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->must_change_password) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        foreach (self::ALLOWED as $allowed) {
            if ($path === $allowed) {
                return $next($request);
            }
        }

        $target = $user->assessor !== null ? '/portal/einstellungen' : '/admin/profil';

        return redirect($target)->with(
            'info',
            'Bitte vergeben Sie ein eigenes Passwort, bevor Sie fortfahren.'
        );
    }
}
