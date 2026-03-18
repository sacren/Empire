<?php

namespace App\Policies;

use App\Models\Cohort;
use App\Models\User;

class CohortPolicy
{
    /**
     * Only admins can manage cohorts.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Cohort $cohort): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Cohort $cohort): bool
    {
        return $user->isAdmin();
    }

    public function attendance(User $user, Cohort $cohort): bool
    {
        return $user->isAdmin();
    }
}
