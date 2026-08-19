<?php

namespace App\Policies;

use App\Models\ServiceType;
use App\Models\User;

class ServiceTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('servicetypes.manage');
    }

    public function view(User $user, ServiceType $serviceType): bool
    {
        return $user->can('servicetypes.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('servicetypes.manage');
    }

    public function update(User $user, ServiceType $serviceType): bool
    {
        return $user->can('servicetypes.manage');
    }

    /** A type already used by a request stays, so history keeps its meaning. */
    public function delete(User $user, ServiceType $serviceType): bool
    {
        return $user->can('servicetypes.manage')
            && $serviceType->serviceRequests()->doesntExist();
    }
}
