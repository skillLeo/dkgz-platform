<?php

namespace App\Policies;

use App\Models\ContentBlock;
use App\Models\User;

class ContentBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('content.view');
    }

    public function view(User $user, ContentBlock $block): bool
    {
        return $user->can('content.view');
    }

    public function update(User $user, ContentBlock $block): bool
    {
        return $user->can('content.edit');
    }
}
