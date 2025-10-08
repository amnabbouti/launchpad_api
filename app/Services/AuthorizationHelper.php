<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Authorization Helper Service
 *
 * Provides utility methods for authorization that were previously in AuthorizationEngine.
 * This service acts as a bridge during the migration to the new authorization system.
 */
class AuthorizationHelper
{
    /**
     * Get the current authenticated user.
     */
    public static function getCurrentUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Check if user is in system scope (super_admin with no organization).
     */
    public static function inSystemScope(?User $user = null): bool
    {
        $user = $user ?? self::getCurrentUser();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin() && !$user->org_id;
    }

    /**
     * Apply organization scope to a query builder.
     *
     * This method ensures that queries are automatically scoped to the user's organization
     * unless the user is a super_admin with system scope.
     */
    public static function applyOrganizationScope(Builder $query, string $table, ?User $user = null): Builder
    {
        $user = $user ?? self::getCurrentUser();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admins with no organization can see all data
        if (self::inSystemScope($user)) {
            return $query;
        }

        // Apply organization scope
        if ($user->org_id) {
            return $query->where("{$table}.org_id", $user->org_id);
        }

        // User has no organization, return empty result
        return $query->whereRaw('1 = 0');
    }

    /**
     * Auto-assign organization to a model based on current user.
     */
    public static function autoAssignOrganization($model, ?User $user = null): void
    {
        $user = $user ?? self::getCurrentUser();

        if (!$user || self::inSystemScope($user)) {
            return;
        }

        if ($user->org_id && !$model->org_id) {
            $model->org_id = $user->org_id;
        }
    }


    /**
     * Get system roles for seeders and migrations
     */
    public static function getSystemRoles(): array
    {
        return [
            'super_admin' => [
                'title' => 'Super Administrator',
                'description' => 'Full system access across all organizations'
            ],
            'admin' => [
                'title' => 'Administrator',
                'description' => 'Organization administrator with full access'
            ],
            'manager' => [
                'title' => 'Manager',
                'description' => 'Organization manager with limited administrative access'
            ],
            'employee' => [
                'title' => 'Employee',
                'description' => 'Standard employee with basic access'
            ]
        ];
    }
}
