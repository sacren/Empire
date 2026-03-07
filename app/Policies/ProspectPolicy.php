<?php

namespace App\Policies;

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
}
