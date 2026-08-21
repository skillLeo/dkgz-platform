<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('assignments.view');
    }

    /**
     * Staff see any assignment; an assessor sees only their own. This is the
     * single check behind both the portal workspace and document downloads.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->can('assignments.view')) {
            return true;
        }

        return $this->owns($user, $assignment);
    }

    /** Only the owning assessor works the job. */
    public function work(User $user, Assignment $assignment): bool
    {
        return $this->owns($user, $assignment) && $assignment->isOpen();
    }

    public function uploadDocuments(User $user, Assignment $assignment): bool
    {
        return $this->work($user, $assignment);
    }

    public function deleteDocument(User $user, Assignment $assignment): bool
    {
        return $this->work($user, $assignment);
    }

    /**
     * Completion is blocked until the report and the customer invoice are both
     * on file. Enforced here so the rule cannot be bypassed by any caller.
     */
    /**
     * Completing needs an open job and its owner — nothing else.
     *
     * Uploaded documents used to be a precondition here as well as in the
     * action. They are not any more: the assessor sends their report to their
     * own customer and tells us it is finished. See DECISIONS.md D-19.
     */
    public function complete(User $user, Assignment $assignment): bool
    {
        return $this->owns($user, $assignment) && $assignment->isOpen();
    }

    public function downloadDocument(User $user, Assignment $assignment): bool
    {
        return $this->view($user, $assignment);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments.edit');
    }

    public function cancel(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments.cancel') && $assignment->isOpen();
    }

    public function export(User $user): bool
    {
        return $user->can('assignments.export');
    }

    private function owns(User $user, Assignment $assignment): bool
    {
        $assessorId = $user->assessor?->id;

        return $assessorId !== null && $assessorId === $assignment->assessor_id;
    }
}
