<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Only admins can set tuition on an enrollment.
     */
    public function setTuition(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins can record payments for an enrollment.
     */
    public function createPayment(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }
}
