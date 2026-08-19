<?php

namespace App\Policies;

use App\Models\Commission;
use App\Models\User;

class CommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('commissions.view');
    }

    /** An assessor may read their own commission rows, never edit them. */
    public function view(User $user, Commission $commission): bool
    {
        if ($user->can('commissions.view')) {
            return true;
        }

        return $user->assessor?->id === $commission->assessor_id;
    }

    public function update(User $user, Commission $commission): bool
    {
        return $user->can('commissions.edit');
    }

    public function settle(User $user, Commission $commission): bool
    {
        return $user->can('commissions.settle')
            && in_array($commission->status, [Commission::STATUS_OPEN, Commission::STATUS_INVOICED], true);
    }

    public function waive(User $user, Commission $commission): bool
    {
        return $user->can('commissions.waive')
            && $commission->status !== Commission::STATUS_SETTLED;
    }

    public function invoice(User $user, Commission $commission): bool
    {
        return $user->can('commissions.invoice')
            && $commission->status === Commission::STATUS_OPEN;
    }

    public function downloadInvoice(User $user, Commission $commission): bool
    {
        return $this->view($user, $commission) && $commission->invoice_path !== null;
    }

    public function export(User $user): bool
    {
        return $user->can('commissions.export');
    }
}
