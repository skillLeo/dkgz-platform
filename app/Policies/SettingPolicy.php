<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['settings.view', 'branding.edit', 'integrations.manage']);
    }

    public function view(User $user, Setting $setting): bool
    {
        return $this->viewAny($user);
    }

    /** Each settings group answers to its own permission. */
    public function update(User $user, Setting $setting): bool
    {
        return match ($setting->group) {
            'branding' => $user->can('branding.edit'),
            'integrations' => $user->can('integrations.manage'),
            default => $user->can('settings.edit'),
        };
    }

    public function updateGroup(User $user, string $group): bool
    {
        return match ($group) {
            'branding' => $user->can('branding.edit'),
            'integrations' => $user->can('integrations.manage'),
            default => $user->can('settings.edit'),
        };
    }
}
