<?php

namespace App\Http\Middleware;

use App\Models\Assessor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The portal boundary. Every approval state has its own destination screen, so
 * a partner always learns where they stand instead of hitting a bare 403.
 */
class EnsureAssessorIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            return redirect()->route('account.blocked');
        }

        $assessor = $user->assessor;

        if ($assessor === null) {
            abort(403, 'Für dieses Konto ist kein Sachverständigenprofil hinterlegt.');
        }

        return match ($assessor->approval_status) {
            Assessor::STATUS_APPROVED => $next($request),
            Assessor::STATUS_PENDING => redirect()->route('registration.pending'),
            Assessor::STATUS_REJECTED => redirect()->route('registration.rejected'),
            Assessor::STATUS_SUSPENDED => redirect()->route('account.blocked'),
            default => abort(403),
        };
    }
}
