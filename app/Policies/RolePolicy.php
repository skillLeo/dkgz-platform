<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    /** super_admin and assessor are structural and may not be recomposed. */
    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.manage')
            && ! in_array($role->name, Permissions::PROTECTED_ROLES, true);
    }

    public function delete(User $user, Role $role): bool
    {
        if (! $user->can('roles.manage')) {
            return false;
        }

        if (in_array($role->name, Permissions::PROTECTED_ROLES, true)) {
            return false;
        }

        // A role still in use cannot be removed out from under its holders.
        return $role->users()->doesntExist();
    }

    /**
     * A user may not strip their own ability to manage roles — that is the one
     * change that cannot be undone from inside the interface.
     */
    public function detachOwnManagement(User $user, Role $role): bool
    {
        return ! $user->hasRole($role->name);
    }
}
