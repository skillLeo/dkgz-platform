<?php

namespace App\Http\Middleware;

use App\Support\Branding;
use App\Support\Content;
use App\Support\Permissions;
use App\Models\ServiceRequest;
use App\Support\Settings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Session keys handed to the page after a redirect.
     *
     * A controller that flashes anything not named here is flashing into the
     * void: the value survives the redirect but never reaches Vue, and the
     * feature quietly does nothing at all.
     */
    private const FLASH_KEYS = [
        'success', 'error', 'warning', 'info',
        'invitationPreview',
        'reset_link_expired',
    ];

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

            // Every key a controller flashes has to be listed here or it never
            // reaches the page. Two features were built against keys that were
            // not — the CSV import preview simply never appeared — so this is
            // one list rather than four hand-written closures.
            'flash' => fn () => collect(self::FLASH_KEYS)
                ->mapWithKeys(fn (string $key) => [$key => $request->session()->get($key)])
                ->all(),

            // Branding is resolved lazily: it only costs a lookup on the first
            // request after a cache bust.
            'branding' => fn () => Branding::forInertia(),

            // How many requests nobody has accepted yet, for the sidebar badge.
            // Lazy and admin-only: a partner never sees it and never pays for
            // the query.
            'requestsInPlacement' => fn () => $user?->can('requests.view')
                ? ServiceRequest::whereIn('status', [
                    ServiceRequest::STATUS_NEW,
                    ServiceRequest::STATUS_MATCHED,
                ])->count()
                : null,

            'app' => [
                // The notice above the header is one line for the whole site.
                // It used to be read from whichever page's content happened to
                // be loaded, so every page but the homepage fell back to the
                // built-in default and the bar said something different as you
                // moved around.
                'announcement' => Content::get('startseite.meldeband.text', 'Bundesweites Netz geprüfter Kfz-Sachverständiger'),
                // The banner appears if either tag is configured: Ads sets
                // cookies for advertising, which needs consent at least as
                // clearly as measurement does.
                'analytics_configured' => filled(Settings::get('integrations.analytics_id'))
                    || filled(Settings::get('integrations.google_ads_id')),
                // The property itself, so the tag can be loaded from the client
                // once consent is given. It is a public identifier — it appears
                // in the script URL of every site that uses it.
                'analytics_id' => Settings::get('integrations.analytics_id') ?: null,
                'google_ads_id' => Settings::get('integrations.google_ads_id') ?: null,
                // The banner appears on every page and belongs to none, so its
                // wording travels with the shared props rather than with
                // whichever page happens to be loaded.
                'cookie_notice' => Content::page('cookies')['banner'] ?? [],
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
