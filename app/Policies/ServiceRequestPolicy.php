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

    public function delete(User $user, ServiceRequest $request): bool
    {
        return $user->can('requests.delete');
    }

    public function restore(User $user, ServiceRequest $request): bool
    {
        return $user->can('requests.delete');
    }
}
