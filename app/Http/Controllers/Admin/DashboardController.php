<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceRequest::class);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'open_requests' => ServiceRequest::whereIn('status', [
                    ServiceRequest::STATUS_NEW, ServiceRequest::STATUS_MATCHED,
                ])->count(),
                'needs_attention' => ServiceRequest::needsAttention()->count(),
                'open_assignments' => Assignment::open()->count(),
                'pending_assessors' => Assessor::pending()->count(),
                'open_commission_cents' => (int) Commission::open()->sum('commission_cents'),
            ],
        ]);
    }
}
