<?php

namespace App\Policies;

use App\Models\Assessor;
use App\Models\User;

class AssessorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('assessors.view');
    }

    public function view(User $user, Assessor $assessor): bool
    {
        if ($user->can('assessors.view')) {
            return true;
        }

        return $user->assessor?->id === $assessor->id;
    }

    public function create(User $user): bool
    {
        return $user->can('assessors.create');
    }

    public function update(User $user, Assessor $assessor): bool
    {
        if ($user->can('assessors.edit')) {
            return true;
        }

        // An assessor maintains their own profile.
        return $user->assessor?->id === $assessor->id;
    }

    public function approve(User $user, Assessor $assessor): bool
    {
        return $user->can('assessors.approve')
            && $assessor->approval_status === Assessor::STATUS_PENDING;
    }

    public function reject(User $user, Assessor $assessor): bool
    {
        return $user->can('assessors.reject')
            && $assessor->approval_status === Assessor::STATUS_PENDING;
    }

    public function suspend(User $user, Assessor $assessor): bool
    {
        return $user->can('assessors.suspend')
            && $assessor->approval_status === Assessor::STATUS_APPROVED;
    }

    public function unsuspend(User $user, Assessor $assessor): bool
    {
        return $user->can('assessors.suspend')
            && $assessor->approval_status === Assessor::STATUS_SUSPENDED;
    }

    public function delete(User $user, Assessor $assessor): bool
    {
        return $user->can('assessors.delete');
    }

    public function restore(User $user, Assessor $assessor): bool
    {
        return $user->can('assessors.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('assessors.export');
    }
}
