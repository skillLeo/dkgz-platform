<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Assessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The three dead-end states a partner can land in. Each one explains where the
 * account stands rather than showing a bare 403.
 */
class AccountStatusController extends Controller
{
    public function pending(Request $request): Response|RedirectResponse
    {
        $assessor = $request->user()?->assessor;

        if ($assessor?->approval_status === Assessor::STATUS_APPROVED) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Auth/PruefungLaeuft', [
            'assessor' => $assessor === null ? null : [
                'company_name' => $assessor->company_name,
                'reference' => sprintf('REG-%s-%05d', $assessor->created_at->format('Y'), $assessor->id),
                'submitted_at' => $assessor->created_at,
                'has_document' => $assessor->qualification_document_path !== null,
                'has_service_areas' => $assessor->serviceAreas()->exists(),
                'has_service_types' => $assessor->serviceTypes()->exists(),
            ],
        ]);
    }

    public function rejected(Request $request): Response|RedirectResponse
    {
        $assessor = $request->user()?->assessor;

        if ($assessor?->approval_status !== Assessor::STATUS_REJECTED) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Auth/RegistrierungAbgelehnt', [
            'reason' => $assessor->rejection_reason,
            'reference' => sprintf('REG-%s-%05d', $assessor->created_at->format('Y'), $assessor->id),
            'decidedAt' => $assessor->updated_at,
        ]);
    }

    public function blocked(Request $request): Response
    {
        $assessor = $request->user()?->assessor;

        return Inertia::render('Auth/KontoGesperrt', [
            'reason' => $assessor?->suspension_reason,
            'suspendedAt' => $assessor?->suspended_at,
            'partnerId' => $assessor === null ? null : $assessor->partnerId(),
        ]);
    }
}
