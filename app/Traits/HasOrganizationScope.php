<?php

namespace App\Traits;

use App\Services\AuthorizationEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

trait HasOrganizationScope
{
    // Boot the trait - add authorization scopes and event listeners
    protected static function bootHasOrganizationScope()
    {
        $resource = static::getResourceName();

        // Apply organization scope to all queries
        static::addGlobalScope('authorization', function (Builder $builder) use ($resource) {
            if (static::shouldSkipAuthorization($builder)) {
                return;
            }

            AuthorizationEngine::applyOrganizationScope($builder, $resource);
        });

        // Check authorization on model creation
        static::creating(function (Model $model) use ($resource) {
            if (AuthorizationEngine::shouldSkipAuthorization()) {
                return;
            }

            AuthorizationEngine::authorize('create', $resource, $model);
            AuthorizationEngine::autoAssignOrganization($model);
        });

        // Check authorization on model update
        static::updating(function (Model $model) use ($resource) {
            if (AuthorizationEngine::shouldSkipAuthorization()) {
                return;
            }

            AuthorizationEngine::authorize('update', $resource, $model);
        });

        // Check authorization on model deletion
        static::deleting(function (Model $model) use ($resource) {
            if (AuthorizationEngine::shouldSkipAuthorization()) {
                return;
            }

            AuthorizationEngine::authorize('delete', $resource, $model);
        });
    }

    // Check if current user can perform action on this model
    public function canAccess(string $action): bool
    {
        $resource = static::getResourceName();

        return AuthorizationEngine::can($action, $resource, $this);
    }

    // Check if model belongs to current user's organization
    public function belongsToCurrentOrganization(): bool
    {
        $user = AuthorizationEngine::getCurrentUser();

        if (! $user) {
            return false;
        }

        if (AuthorizationEngine::isSuperAdmin($user)) {
            return true;
        }

        return $user->org_id && $this->org_id === $user->org_id;
    }

    // Get resource name from model class
    protected static function getResourceName(): string
    {
        $className = class_basename(static::class);

        return strtolower($className).'s';
    }

    // Skip authorization for auth routes and console commands
    protected static function shouldSkipAuthorization(Builder $builder): bool
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
}
