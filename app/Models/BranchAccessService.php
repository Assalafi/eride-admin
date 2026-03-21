<?php

namespace App\Services;

use App\Models\User;

class BranchAccessService
{
    /**
     * Get branch IDs that a user has access to
     */
    public static function getUserBranchIds(User $user): array
    {
        // Super Admin and Accountant can access all branches
        if ($user->hasRole(['Super Admin', 'Accountant'])) {
            return \App\Models\Branch::pluck('id')->toArray();
        }

        // For other users, get their assigned branches
        $branchIds = $user->branch_ids;

        // Fallback to legacy branch_id for backward compatibility
        if (empty($branchIds) && $user->branch_id) {
            return [$user->branch_id];
        }

        return $branchIds ?: [];
    }

    /**
     * Apply branch filtering to a query based on user role
     */
    public static function applyBranchFilter($query, User $user, string $branchColumn = 'branch_id')
    {
        $branchIds = self::getUserBranchIds($user);

        if (!empty($branchIds)) {
            $query->whereIn($branchColumn, $branchIds);
        }

        return $query;
    }

    /**
     * Apply branch filtering through relationship
     */
    public static function applyBranchFilterThroughRelation($query, User $user, string $relation, string $branchColumn = 'branch_id')
    {
        $branchIds = self::getUserBranchIds($user);

        if (!empty($branchIds)) {
            $query->whereHas($relation, function ($q) use ($branchIds, $branchColumn) {
                $q->whereIn($branchColumn, $branchIds);
            });
        }

        return $query;
    }

    /**
     * Check if user can access a specific branch
     */
    public static function canAccessBranch(User $user, int $branchId): bool
    {
        if ($user->hasRole(['Super Admin', 'Accountant'])) {
            return true;
        }

        return in_array($branchId, self::getUserBranchIds($user));
    }

    /**
     * Get branches for dropdown/filter (for views)
     */
    public static function getAvailableBranchesForUser(User $user)
    {
        if ($user->hasRole(['Super Admin', 'Accountant'])) {
            return \App\Models\Branch::all();
        }

        return $user->branches;
    }
}
