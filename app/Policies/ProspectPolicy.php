<?php

namespace App\Policies;

use App\Enums\ProspectStatus;
use App\Models\Prospect;
use App\Models\User;

class ProspectPolicy
{
    /**
     * Admins and staff can view the prospect list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Admins can view any prospect; staff can only view prospects assigned to them.
     */
    public function view(User $user, Prospect $prospect): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $prospect->assigned_to === $user->id;
    }

    /**
     * Any authenticated staff member or admin can create prospects.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Admins can update any prospect; staff can only update prospects assigned to them.
     */
    public function update(User $user, Prospect $prospect): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $prospect->assigned_to === $user->id;
    }

    /**
     * Only admins can delete prospects.
     */
    public function delete(User $user, Prospect $prospect): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins can reassign prospects.
     */
    public function reassign(User $user, Prospect $prospect): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins and assigned staff can enroll a qualified, unenrolled prospect.
     */
    public function enroll(User $user, Prospect $prospect): bool
    {
        if ($prospect->status !== ProspectStatus::Qualified) {
            return false;
        }

        if ($prospect->enrollment !== null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $prospect->assigned_to === $user->id;
    }

    /**
     * Admins can upload documents for any prospect; staff can upload for assigned prospects.
     */
    public function uploadDocument(User $user, Prospect $prospect): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $prospect->assigned_to === $user->id;
    }

    /**
     * Only admins can review (approve/reject) documents.
     */
    public function reviewDocument(User $user, Prospect $prospect): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins can delete documents.
     */
    public function deleteDocument(User $user, Prospect $prospect): bool
    {
        return $user->isAdmin();
    }
}
