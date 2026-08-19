<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invitations.view');
    }

    public function view(User $user, Invitation $invitation): bool
    {
        return $user->can('invitations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('invitations.create');
    }

    public function resend(User $user, Invitation $invitation): bool
    {
        return $user->can('invitations.create') && $invitation->isPending();
    }

    public function revoke(User $user, Invitation $invitation): bool
    {
        return $user->can('invitations.revoke') && ! $invitation->isAccepted();
    }
}
