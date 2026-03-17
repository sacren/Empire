<?php

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Only admins can set tuition on an enrollment that is not fully paid.
     */
    public function setTuition(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin() && $enrollment->status !== EnrollmentStatus::Paid;
    }

    /**
     * Only admins can record payments for an enrollment that is not fully paid.
     */
    public function createPayment(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin() && $enrollment->status !== EnrollmentStatus::Paid;
    }

    public function graduate(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }

    public function manageAttendance(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }

    public function manageMilestones(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }
}
