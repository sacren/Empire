<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RoundRobinAssignmentService
{
    private const CACHE_KEY = 'round_robin_last_assigned_user_id';

    public function nextStaffMember(): ?User
    {
        $staffMembers = User::query()
            ->where('role', UserRole::Staff->value)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($staffMembers->isEmpty()) {
            return null;
        }

        $lastAssignedId = Cache::get(self::CACHE_KEY, 0);

        $next = $staffMembers->first(fn (User $user) => $user->id > $lastAssignedId)
            ?? $staffMembers->first();

        Cache::put(self::CACHE_KEY, $next->id);

        return $next;
    }
}
