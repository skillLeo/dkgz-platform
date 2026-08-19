<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view') || $user->is($model);
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.edit') || $user->is($model);
    }

    /** Nobody deletes themselves, and the last super_admin is undeletable. */
    public function delete(User $user, User $model): bool
    {
        if (! $user->can('users.delete') || $user->is($model)) {
            return false;
        }

        if ($model->hasRole('super_admin')) {
            return User::role('super_admin')->count() > 1;
        }

        return true;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('users.delete');
    }
}
