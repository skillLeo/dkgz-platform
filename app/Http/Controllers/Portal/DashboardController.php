<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\RequestMatch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $assessor = $request->user()->assessor;

        return Inertia::render('Portal/Dashboard', [
            'stats' => [
                'open_requests' => RequestMatch::where('assessor_id', $assessor->id)->pending()->count(),
                'open_assignments' => Assignment::where('assessor_id', $assessor->id)->open()->count(),
                'completed_assignments' => Assignment::where('assessor_id', $assessor->id)
                    ->where('status', Assignment::STATUS_COMPLETED)->count(),
                'open_commission_cents' => (int) Commission::where('assessor_id', $assessor->id)
                    ->open()->sum('commission_cents'),
            ],
            'availability' => $assessor->is_available,
        ]);
    }
}
