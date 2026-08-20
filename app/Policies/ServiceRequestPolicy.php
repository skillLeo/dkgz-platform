<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('requests.view');
    }

    public function view(User $user, ServiceRequest $request): bool
    {
        return $user->can('requests.view');
    }

    public function export(User $user): bool
    {
        return $user->can('requests.export');
    }

    public function reassign(User $user, ServiceRequest $request): bool
    {
        return $user->can('requests.reassign') && $request->isOpen();
    }

    /**
     * Closing a request by hand ends it for everyone it was sent to, so it sits
     * with reassignment rather than with plain viewing.
     */
    public function close(User $user, ServiceRequest $request): bool
    {
        return $user->can('requests.reassign');
    }

    public function delete(User $user, ServiceRequest $request): bool
    {
        return $user->can('requests.delete');
    }

    public function restore(User $user, ServiceRequest $request): bool
    {
        return $user->can('requests.delete');
    }
}
