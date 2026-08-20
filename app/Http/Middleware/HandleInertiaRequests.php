<?php

namespace App\Http\Middleware;

use App\Support\Branding;
use App\Support\Permissions;
use App\Support\Settings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Shared props. The permission list is here so Vue can hide what a user
     * cannot do — hiding a button is never the security control, every route
     * and policy enforces independently.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->fullName(),
                    'email' => $user->email,
                    'avatar' => $user->avatar_path,
                    'is_assessor' => $user->hasRole('assessor'),
                    'assessor' => $user->assessor === null ? null : [
                        'id' => $user->assessor->id,
                        'partner_id' => $user->assessor->partnerId(),
                        'cover' => [
                            'valid_until' => $user->assessor->liabilityCoverValidUntil(),
                            'has_lapsed' => $user->assessor->liabilityCoverHasLapsed(),
                            'expires_soon' => $user->assessor->liabilityCoverExpiresSoon(),
                            'blocks_matching' => Settings::bool('business.require_valid_liability_cover', true),
                        ],
                        'company_name' => $user->assessor->company_name,
                        'is_available' => $user->assessor->is_available,
                        'approval_status' => $user->assessor->approval_status,
                    ],
                ],
                'permissions' => $user === null
                    ? []
                    : $this->resolvePermissions($user),
                'roles' => $user?->getRoleNames()->all() ?? [],
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],

            // Branding is resolved lazily: it only costs a lookup on the first
            // request after a cache bust.
            'branding' => fn () => Branding::forInertia(),

            'app' => [
                'analytics_configured' => filled(Settings::get('integrations.analytics_id')),
                'poll_seconds' => max(15, Settings::int('business.notification_cadence_minutes', 45)),
                'name' => Settings::get('branding.platform_name', config('app.name')),
                'phone' => Settings::get('contact.phone'),
                'office_hours' => Settings::get('contact.office_hours'),
                'support_email' => Settings::get('contact.support_email'),
            ],

            'ziggy' => fn () => [
                'location' => $request->url(),
            ],
        ]);
    }

    /** @return list<string> */
    private function resolvePermissions($user): array
    {
        // super_admin holds everything implicitly through Gate::before, so the
        // shared list has to say so explicitly or the UI would hide its own
        // controls from the one role that owns them.
        if ($user->hasRole('super_admin')) {
            return Permissions::all();
        }

        return $user->getAllPermissions()->pluck('name')->all();
    }
}
