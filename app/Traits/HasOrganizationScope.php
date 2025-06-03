<?php

namespace App\Traits;

use App\Constants\ErrorMessages;
use App\Exceptions\UnauthorizedAccessException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

trait HasOrganizationScope
{
    /** Cache the authenticated user to avoid repeated queries. */
    protected static $cachedAuthUser = null;

    protected static $cacheKey = null;

    /**
     * Validate organization authorization for creating a record.
     */
    public static function validateCreateOrgAuthorization(Model $model): void
    {
        $user = static::getCachedAuthUser();

        if (! $user) {
            throw new UnauthorizedAccessException(ErrorMessages::UNAUTHORIZED);
        }

        if (static::isSuperAdminUser($user)) {
            return;
        }

        if (! $user->org_id) {
            throw new UnauthorizedAccessException(ErrorMessages::UNAUTHORIZED);
        }

        if ($model->org_id && $model->org_id !== $user->org_id) {
            throw new UnauthorizedAccessException(ErrorMessages::CROSS_ORG_ACCESS);
        }

        // Auto-assign organization if not set
        if (! $model->org_id) {
            $model->org_id = $user->org_id;
        }
    }

    /**
     * Validate organization authorization for updating a record.
     */
    public static function validateUpdateOrgAuthorization(Model $model): void
    {
        $user = static::getCachedAuthUser();

        if (! $user) {
            throw new UnauthorizedAccessException(ErrorMessages::UNAUTHORIZED);
        }

        if (static::isSuperAdminUser($user)) {
            return;
        }

        if ($model->org_id !== $user->org_id) {
            throw new UnauthorizedAccessException(ErrorMessages::CROSS_ORG_ACCESS);
        }

        if ($model->isDirty('org_id') && $model->org_id !== $user->org_id) {
            throw new UnauthorizedAccessException(ErrorMessages::CROSS_ORG_ACCESS);
        }
    }

    /**
     * Validate organization authorization for deleting a record.
     */
    public static function validateDeleteOrgAuthorization(Model $model): void
    {
        $user = static::getCachedAuthUser();

        if (! $user) {
            throw new UnauthorizedAccessException(ErrorMessages::UNAUTHORIZED);
        }

        if (static::isSuperAdminUser($user)) {
            return;
        }

        if ($model->org_id !== $user->org_id) {
            throw new UnauthorizedAccessException(ErrorMessages::CROSS_ORG_ACCESS);
        }
    }

    /**
     * Check if a record exists globally but is blocked by organization scope.
     */
    public static function findByIdWithAuthCheck($id)
    {
        $record = static::withoutGlobalScopes()->find($id);

        if (! $record) {
            return null;
        }

        $user = static::getCachedAuthUser();

        if (! $user) {
            throw new UnauthorizedAccessException(ErrorMessages::UNAUTHORIZED);
        }

        if (static::isSuperAdminUser($user)) {
            return $record;
        }

        if (! $user->org_id || $record->org_id !== $user->org_id) {
            throw new UnauthorizedAccessException(ErrorMessages::CROSS_ORG_ACCESS);
        }

        return $record;
    }

    /**
     * Check if a record exists but access is denied due to organization restrictions.
     */
    public static function checkCrossOrganizationAccess($id)
    {
        $record = static::withoutGlobalScopes()->find($id);

        if (! $record) {
            return false;
        }

        $user = static::getCachedAuthUser();

        if (! $user) {
            throw new UnauthorizedAccessException(ErrorMessages::UNAUTHORIZED);
        }

        if (static::isSuperAdminUser($user)) {
            return false;
        }

        if ($user->org_id && $record->org_id && $record->org_id !== $user->org_id) {
            throw new UnauthorizedAccessException(ErrorMessages::CROSS_ORG_ACCESS);
        }

        return false;
    }

    /**
     * Scope query to a specific organization.
     */
    public function scopeForOrganization(Builder $query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Check if model belongs to current user's organization.
     */
    public function belongsToCurrentOrganization(): bool
    {
        $user = static::getCachedAuthUser();

        if (! $user) {
            return false;
        }

        if (static::isSuperAdminUser($user)) {
            return true;
        }

        return $user->org_id && $this->org_id === $user->org_id;
    }

    /**
     * Validate that two models belong to the same organization.
     */
    public function validateSameOrganization(Model $relatedModel): bool
    {
        if (! $relatedModel->org_id || ! $this->org_id) {
            return true;
        }

        return $this->org_id === $relatedModel->org_id;
    }

    /**
     * Get organization scope status for debugging.
     */
    public function getOrganizationScopeStatus(): array
    {
        $user = static::getCachedAuthUser();

        return [
            'user_authenticated' => (bool) $user,
            'user_org_id' => $user?->org_id,
            'model_org_id' => $this->org_id,
            'is_super_admin' => $user ? static::isSuperAdminUser($user) : false,
            'belongs_to_current_org' => $this->belongsToCurrentOrganization(),
        ];
    }

    protected static function bootHasOrganizationScope()
    {
        // Skip all scoping and event listeners for User model to prevent authentication issues
        if (static::class === \App\Models\User::class) {
            return;
        }

        static::addGlobalScope('organization', function (Builder $builder) {
            if (static::shouldSkipOrganizationScope($builder)) {
                return;
            }

            $user = static::getCachedAuthUser();

            if (! $user) {
                if ($builder->getQuery()->wheres) {
                    static::checkRecordAccess($builder->getModel()->id);
                }
                $builder->whereRaw('1 = 0');

                return;
            }

            if (static::isSuperAdminUser($user)) {
                return;
            }

            if ($user->org_id) {
                if ($builder->getQuery()->wheres) {
                    static::checkRecordAccess($builder->getModel()->id);
                }
                $builder->where($builder->getModel()->getTable().'.org_id', $user->org_id);
            } else {
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $model) {
            static::validateCreateOrgAuthorization($model);
        });

        static::updating(function (Model $model) {
            static::validateUpdateOrgAuthorization($model);
        });

        static::deleting(function (Model $model) {
            static::validateDeleteOrgAuthorization($model);
        });
    }

    /**
     * Determine if organization scope should be skipped.
     */
    protected static function shouldSkipOrganizationScope(Builder $builder): bool
    {
        $currentRoute = Route::currentRouteName();
        $request = request();

        return ($currentRoute && in_array($currentRoute, ['login', 'logout', 'register', 'sanctum.csrf-cookie']))
               || ($request && (
                   $request->is('api/login')
                   || $request->is('api/logout')
                   || $request->is('api/register')
                   || $request->is('login')
                   || $request->is('logout')
                   || $request->is('register')
                   || $request->is('sanctum/csrf-cookie')
                   || $request->is('api/sanctum/token')
               ))
               || app()->runningInConsole();
    }

    /**
     * Get cached authenticated user.
     */
    protected static function getCachedAuthUser()
    {
        $currentUserId = Auth::id();

        if (! $currentUserId) {
            static::$cachedAuthUser = null;
            static::$cacheKey = null;

            return null;
        }

        if (static::$cacheKey === $currentUserId && static::$cachedAuthUser) {
            return static::$cachedAuthUser;
        }

        static::$cachedAuthUser = \App\Models\User::withoutGlobalScopes()->find($currentUserId);
        static::$cacheKey = $currentUserId;

        return static::$cachedAuthUser;
    }

    /**
     * Check if user is super admin.
     */
    protected static function isSuperAdminUser($user): bool
    {
        if (! $user || ! $user->role_id) {
            return false;
        }

        $role = \App\Models\Role::withoutGlobalScopes()->find($user->role_id);

        return $role && $role->slug === 'super_admin';
    }

    /**
     * Check record access and throw appropriate exceptions.
     */
    protected static function checkRecordAccess($id)
    {
        $record = static::withoutGlobalScopes()->find($id);

        if (! $record) {
            return false;
        }

        $user = static::getCachedAuthUser();

        if (! $user) {
            throw new UnauthorizedAccessException(ErrorMessages::UNAUTHORIZED);
        }

        if (static::isSuperAdminUser($user)) {
            return true;
        }

        if (! $user->org_id || $record->org_id !== $user->org_id) {
            throw new UnauthorizedAccessException(ErrorMessages::CROSS_ORG_ACCESS);
        }

        return true;
    }
}
