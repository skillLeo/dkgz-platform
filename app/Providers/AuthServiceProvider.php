<?php

namespace App\Providers;

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\ContentBlock;
use App\Models\EmailTemplate;
use App\Models\Faq;
use App\Models\Invitation;
use App\Models\Page;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\User;
use App\Policies\AssessorPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\CommissionPolicy;
use App\Policies\ContentBlockPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\FaqPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\PagePolicy;
use App\Policies\RolePolicy;
use App\Policies\ServiceRequestPolicy;
use App\Policies\ServiceTypePolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ServiceRequest::class => ServiceRequestPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        Assessor::class => AssessorPolicy::class,
        Commission::class => CommissionPolicy::class,
        Invitation::class => InvitationPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Setting::class => SettingPolicy::class,
        ContentBlock::class => ContentBlockPolicy::class,
        Page::class => PagePolicy::class,
        Faq::class => FaqPolicy::class,
        EmailTemplate::class => EmailTemplatePolicy::class,
        ServiceType::class => ServiceTypePolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            // A deactivated account can do nothing, whatever its roles say.
            // This has to sit in `before`, not `after`: an `after` callback can
            // only fill in a null result, it cannot revoke a granted one.
            if (! $user->is_active) {
                return false;
            }

            // super_admin holds everything by definition.
            if ($user->hasRole('super_admin')) {
                return true;
            }

            return null;
        });
    }
}
