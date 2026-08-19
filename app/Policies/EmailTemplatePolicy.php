<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('emails.view');
    }

    public function view(User $user, EmailTemplate $template): bool
    {
        return $user->can('emails.view');
    }

    public function update(User $user, EmailTemplate $template): bool
    {
        return $user->can('emails.edit');
    }

    public function preview(User $user, EmailTemplate $template): bool
    {
        return $user->can('emails.view');
    }

    public function sendTest(User $user): bool
    {
        return $user->can('emails.test');
    }
}
