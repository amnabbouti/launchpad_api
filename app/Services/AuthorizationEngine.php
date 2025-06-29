<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Constants\ErrorMessages;
use App\Exceptions\UnauthorizedAccessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class AuthorizationEngine
{
    /**
     * System role definitions with permissions.
     */
    private static $systemRoles = [
        'super_admin' => [
            'title' => 'Super Administrator',
            'description' => 'Full system access - can manage everything',
            'forbidden' => [],
            'is_system' => true,
        ],
        'manager' => [
            'title' => 'Manager',
            'description' => 'Organization manager - can manage organization, users, billing, and plans',
            'forbidden' => null, // Will be loaded from Permissions::getManagerForbiddenPermissions()
            'is_system' => true,
        ],
        'employee' => [
            'title' => 'Employee',
            'description' => 'Employee - can use full inventory system and basic operations',
            'forbidden' => null, // Will be loaded from Permissions::getEmployeeForbiddenPermissions()
            'is_system' => true,
        ],
    ];

    /**
     * Authorize action or throw exception.
     */
    public static function authorize(string $action, string $resource, $targetModel = null): void
    {
        if (!static::can($action, $resource, $targetModel)) {
            throw new UnauthorizedAccessException(ErrorMessages::FORBIDDEN);
        }
    }

    /**
     * Check if user can perform action on resource.
     */
    public static function can(string $action, string $resource, $targetModel = null): bool
    {
        $user = static::getCurrentUser();

        if (!$user) {
            return false;
        }

        if (static::shouldSkipAuthorization()) {
            return true;
        }

        if (static::isForbidden($action, $resource, $user, $targetModel)) {
            return false;
        }

        if (!static::hasOrganizationAccess($action, $resource, $targetModel, $user)) {
            return false;
        }

        if (!static::hasBusinessRuleAccess($action, $resource, $targetModel, $user)) {
            return false;
        }

        return true;
    }

    /**
     * Check if action is forbidden for user's role.
     */
    public static function isForbidden(string $action, string $resource, User $user, $targetModel = null): bool
    {
        if (static::isSuperAdmin($user)) {
            return false;
        }

        if (!$user->role) {
            return true;
        }

        $roleSlug = $user->role->slug;

        $actionsToCheck = [
            "$resource.$action",
            "$resource.$action.others",
            "$resource.$action." . ($targetModel ? static::getTargetType($targetModel, $user) : ''),
        ];

        $forbiddenActions = static::getRoleForbiddenActions($roleSlug);

        foreach ($actionsToCheck as $actionPattern) {
            if (in_array($actionPattern, $forbiddenActions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get forbidden actions for a role.
     */
    public static function getRoleForbiddenActions(string $roleSlug): array
    {
        if (static::isSystemRole($roleSlug)) {
            return \App\Constants\Permissions::getSystemRoleForbiddenPermissions($roleSlug);
        }

        $role = Role::where('slug', $roleSlug)->where('is_system', false)->first();
        return $role ? $role->getForbidden() : [];
    }

    /**
     * Get target type for contextual permissions.
     */
    private static function getTargetType($targetModel, User $user): string
    {
        if (!$targetModel) {
            return '';
        }

        if ($targetModel instanceof User) {
            return $targetModel->id === $user->id ? 'self' : 'others';
        }

        if ($targetModel instanceof User && $targetModel->role) {
            return $targetModel->role->slug;
        }

        return '';
    }

    /**
     * Get all system role definitions.
     */
    public static function getSystemRoles(): array
    {
        return static::$systemRoles;
    }

    /**
     * Check if a role is a system role.
     */
    public static function isSystemRole(string $roleSlug): bool
    {
        return isset(static::$systemRoles[$roleSlug]);
    }

    /**
     * Get system role definition.
     */
    public static function getSystemRole(string $roleSlug): ?array
    {
        return static::$systemRoles[$roleSlug] ?? null;
    }

    /**
     * Get available system roles for user assignment.
     */
    public static function getAvailableSystemRoles(): array
    {
        return [
            'super_admin' => static::$systemRoles['super_admin']['title'],
            'manager' => static::$systemRoles['manager']['title'],
            'employee' => static::$systemRoles['employee']['title'],
        ];
    }

    /**
     * Check if user can assign a specific role.
     */
    public static function canAssignRole(string $roleSlug, ?User $user = null): bool
    {
        $user = $user ?? static::getCurrentUser();

        if (!$user) {
            return false;
        }

        if (static::isSuperAdmin($user)) {
            return true;
        }

        if ($roleSlug === 'super_admin') {
            return false;
        }

        if ($roleSlug === 'manager') {
            return false;
        }

        if ($user->isManager()) {
            return $roleSlug === 'employee' || !static::isSystemRole($roleSlug);
        }

        return false;
    }

    /**
     * Get roles that a user can assign to others.
     */
    public static function getAssignableRoles(?User $user = null): array
    {
        $user = $user ?? static::getCurrentUser();

        if (!$user) {
            return [];
        }

        $availableRoles = static::getAvailableRoles($user->org_id);

        return array_filter($availableRoles, function ($role) use ($user) {
            return static::canAssignRole($role['slug'], $user);
        });
    }

    /**
     * Check if user can create custom roles.
     */
    public static function canCreateCustomRole(?User $user = null): bool
    {
        $user = $user ?? static::getCurrentUser();

        if (!$user) {
            return false;
        }

        if (static::isSuperAdmin($user)) {
            return true;
        }

        if ($user->isManager() && $user->org_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can modify a specific custom role.
     */
    public static function canModifyCustomRole(Role $role, ?User $user = null): bool
    {
        $user = $user ?? static::getCurrentUser();

        if (!$user) {
            return false;
        }

        if ($role->isSystemRole()) {
            return false;
        }

        if (static::isSuperAdmin($user)) {
            return true;
        }

        if ($user->isManager() && $user->org_id === $role->org_id) {
            return true;
        }

        return false;
    }

    /**
     * Get permissions that are always forbidden for managers.
     */
    public static function getRequiredForbiddenActionsForManagers(): array
    {
        return \App\Constants\Permissions::getRequiredForbiddenKeys();
    }

    /**
     * Validate custom role for manager restrictions.
     */
    public static function validateCustomRoleForManager(array $forbiddenActions): array
    {
        $errors = [];
        $requiredForbidden = static::getRequiredForbiddenActionsForManagers();

        foreach ($requiredForbidden as $required) {
            if (!in_array($required, $forbiddenActions)) {
                $errors[] = "Custom role must forbid: {$required}";
            }
        }

        return $errors;
    }

    /**
     * Validate custom role permissions.
     */
    public static function validateCustomRolePermissions(array $forbiddenActions): array
    {
        return static::validateCustomRoleForManager($forbiddenActions);
    }

    /**
     * Get all permissions that managers can grant to users.
     */
    public static function getAvailablePermissionsForManagers(): array
    {
        return \App\Constants\Permissions::getAvailablePermissionsForManagers();
    }

    /**
     * Get permissions that must always be forbidden.
     */
    public static function getRequiredForbiddenPermissions(): array
    {
        return \App\Constants\Permissions::getRequiredForbiddenPermissions();
    }

    /**
     * Get all available roles for an organization.
     */
    public static function getAvailableRoles(?int $orgId = null): array
    {
        $roles = [];

        $systemRoles = Role::systemRoles()->get();
        foreach ($systemRoles as $role) {
            $roles[] = [
                'slug' => $role->slug,
                'title' => $role->title,
                'description' => $role->description,
                'type' => 'system',
                'is_system' => true,
            ];
        }

        if ($orgId) {
            $customRoles = Role::customRoles()->forOrganization($orgId)->get();
            foreach ($customRoles as $role) {
                $roles[] = [
                    'slug' => $role->slug,
                    'title' => $role->title,
                    'description' => $role->description,
                    'type' => 'custom',
                    'is_system' => false,
                ];
            }
        }

        return $roles;
    }

    /**
     * Check if a role exists.
     */
    public static function roleExists(string $roleSlug, ?int $orgId = null): bool
    {
        if (Role::where('slug', $roleSlug)->where('is_system', true)->exists()) {
            return true;
        }

        if ($orgId) {
            return Role::where('slug', $roleSlug)
                ->where('org_id', $orgId)
                ->where('is_system', false)
                ->exists();
        }

        return false;
    }

    /**
     * Apply organization scope to query.
     */
    public static function applyOrganizationScope(Builder $query, string $resource): Builder
    {
        if (in_array($resource, ['users', 'roles', 'plans'])) {
            return $query;
        }

        $user = static::getCurrentUser();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (static::isSuperAdmin($user)) {
            return $query;
        }

        if (!$user->org_id) {
            return $query->whereRaw('1 = 0');
        }

        if ($resource === 'licenses') {
            return $query->where($query->getModel()->getTable() . '.organization_id', $user->org_id);
        }

        return $query->where($query->getModel()->getTable() . '.org_id', $user->org_id);
    }

    /**
     * Filter array of models to only visible ones.
     */
    public static function filterVisibleModels($models, string $resource, ?User $user = null): array
    {
        $user = $user ?? static::getCurrentUser();

        if (!$user) {
            return [];
        }

        return array_filter($models, function ($model) use ($resource, $user) {
            return static::can('view', $resource, $model);
        });
    }

    /**
     * Auto-assign organization to model.
     */
    public static function autoAssignOrganization(Model $model, ?User $user = null): void
    {
        $user = $user ?? static::getCurrentUser();

        if (!$user || static::isSuperAdmin($user)) {
            return;
        }

        if (!$model->org_id && $user->org_id) {
            $model->org_id = $user->org_id;
        }
    }

    /**
     * Get current authenticated user.
     */
    public static function getCurrentUser(): ?User
    {
        return Auth::guard('api')->user() ?? Auth::user();
    }

    /**
     * Check if user is super admin.
     */
    public static function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? static::getCurrentUser();
        return $user && $user->role && $user->role->slug === 'super_admin';
    }

    /**
     * Check if should skip authorization.
     */
    public static function shouldSkipAuthorization(): bool
    {
        return app()->runningInConsole() && app()->environment(['local', 'testing']);
    }

    /**
     * Get resource name from model.
     */
    public static function getResourceFromModel(Model $model): string
    {
        $className = class_basename($model);
        return strtolower($className) . 's';
    }

    /**
     * Check organization access rules.
     */
    private static function hasOrganizationAccess(string $action, string $resource, $targetModel, User $user): bool
    {
        if (static::isSuperAdmin($user)) {
            return true;
        }

        if (!$targetModel || !method_exists($targetModel, 'getTable')) {
            return true;
        }

        if ($resource === 'users') {
            return static::hasUserVisibilityAccess($targetModel, $user);
        }

        if ($resource === 'organizations') {
            return static::hasOrganizationManagementAccess($targetModel, $user);
        }

        if ($resource === 'roles') {
            return true;
        }

        if ($resource === 'plans') {
            return true;
        }

        if ($resource === 'licenses') {
            return static::hasLicenseAccess($targetModel, $user);
        }

        if (!$user->org_id) {
            return false;
        }

        if (isset($targetModel->org_id)) {
            return $targetModel->org_id === $user->org_id;
        }

        return true;
    }

    /**
     * Check business rule access.
     */
    private static function hasBusinessRuleAccess(string $action, string $resource, $targetModel, User $user): bool
    {
        if ($resource === 'users' && $action === 'delete' && $targetModel && $targetModel->id === $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Check user visibility access.
     */
    private static function hasUserVisibilityAccess(User $targetUser, User $currentUser): bool
    {
        if (static::isSuperAdmin($currentUser)) {
            return true;
        }

        if ($targetUser->id === $currentUser->id) {
            return true;
        }

        if ($currentUser->isManager() && $targetUser->org_id === $currentUser->org_id) {
            return true;
        }

        return false;
    }

    /**
     * Check organization management access.
     */
    private static function hasOrganizationManagementAccess($targetOrg, User $user): bool
    {
        if (static::isSuperAdmin($user)) {
            return true;
        }

        return isset($targetOrg->id) && $targetOrg->id === $user->org_id;
    }

    /**
     * Check license access.
     */
    private static function hasLicenseAccess($targetLicense, User $user): bool
    {
        if (static::isSuperAdmin($user)) {
            return true;
        }

        return isset($targetLicense->organization_id) && $targetLicense->organization_id === $user->org_id;
    }
}
