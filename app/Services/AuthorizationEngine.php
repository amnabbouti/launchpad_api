<?php

declare(strict_types = 1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Constants\Permissions;
use App\Exceptions\UnauthorizedAccessException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use function in_array;

class AuthorizationEngine {
    /**
     * System role definitions with permissions.
     */
    private static array $systemRoles = [
        'super_admin' => [
            'title'       => 'Super Administrator',
            'description' => 'Full system access - can manage everything',
            'forbidden'   => [],
            'is_system'   => true,
        ],
        'manager' => [
            'title'       => 'Manager',
            'description' => 'Organization manager - can manage organization, users, billing, and plans',
            'forbidden'   => null,
            'is_system'   => true,
        ],
        'employee' => [
            'title'       => 'Employee',
            'description' => 'Employee - can use full inventory system and basic operations',
            'forbidden'   => null,
            'is_system'   => true,
        ],
    ];

    /**
     * Apply organization scope to query.
     */
    public static function applyOrganizationScope(Builder $query, string $resource): Builder {
        if (in_array($resource, ['users', 'roles', 'organizations'], true)) {
            return $query;
        }

        $user = self::getCurrentUser();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (self::isSuperAdmin($user)) {
            return $query;
        }

        if (! $user->org_id) {
            return $query->whereRaw('1 = 0');
        }

        if ($resource === 'licenses') {
            return $query->where($query->getModel()->getTable() . '.org_id', $user->org_id);
        }

        return $query->where($query->getModel()->getTable() . '.org_id', $user->org_id);
    }

    /**
     * Authorize action or throw exception.
     */
    public static function authorize(string $action, string $resource, $targetModel = null): void {
        if (! self::can($action, $resource, $targetModel)) {
            throw new UnauthorizedAccessException(ErrorMessages::FORBIDDEN);
        }
    }

    /**
     * Auto-assign organization to model.
     */
    public static function autoAssignOrganization(Model $model, ?User $user = null): void {
        $user = $user ?? self::getCurrentUser();

        if (! $user || self::isSuperAdmin($user)) {
            return;
        }

        if (! $model->org_id && $user->org_id) {
            $model->org_id = $user->org_id;
        }
    }

    /**
     * Check if user can perform action on resource.
     */
    public static function can(string $action, string $resource, $targetModel = null): bool {
        $user = self::getCurrentUser();

        if (! $user) {
            return false;
        }

        if (self::shouldSkipAuthorization()) {
            return true;
        }

        if (self::isForbidden($action, $resource, $user, $targetModel)) {
            return false;
        }

        if (! self::hasOrganizationAccess($resource, $targetModel, $user)) {
            return false;
        }

        return ! (! self::hasBusinessRuleAccess($action, $resource, $targetModel, $user));
    }

    /**
     * Check if the user can assign a specific role.
     */
    public static function canAssignRole(string $roleSlug, ?User $user = null): bool {
        $user = $user ?? self::getCurrentUser();

        if (! $user) {
            return false;
        }

        if (self::isSuperAdmin($user)) {
            return true;
        }

        if ($roleSlug === 'super_admin') {
            return false;
        }

        if ($roleSlug === 'manager') {
            return false;
        }

        if ($user->isManager()) {
            return $roleSlug === 'employee' || ! self::isSystemRole($roleSlug);
        }

        return false;
    }

    /**
     * Check if a user can create custom roles.
     */
    public static function canCreateCustomRole(?User $user = null): bool {
        $user = $user ?? self::getCurrentUser();

        if (! $user) {
            return false;
        }

        if (self::isSuperAdmin($user)) {
            return true;
        }

        return $user->isManager() && $user->org_id;
    }

    /**
     * Check if a user can modify a specific custom role.
     */
    public static function canModifyCustomRole(Role $role, ?User $user = null): bool {
        $user = $user ?? self::getCurrentUser();

        if (! $user) {
            return false;
        }

        if ($role->isSystemRole()) {
            return false;
        }

        if (self::isSuperAdmin($user)) {
            return true;
        }

        return $user->isManager() && $user->org_id === $role->org_id;
    }

    /**
     * Filter an array of models to only visible ones.
     */
    public static function filterVisibleModels($models, string $resource, ?User $user = null): array {
        $user = $user ?? self::getCurrentUser();

        if (! $user) {
            return [];
        }

        return array_filter($models, static fn ($model) => self::can('view', $resource, $model));
    }

    /**
     * Get roles that a user can assign to others.
     */
    public static function getAssignableRoles(?User $user = null): array {
        $user = $user ?? self::getCurrentUser();

        if (! $user) {
            return [];
        }

        $availableRoles = self::getAvailableRoles($user->org_id);

        return array_filter($availableRoles, static fn ($role) => self::canAssignRole($role['slug'], $user));
    }

    /**
     * Get all permissions that managers can grant to users.
     */
    public static function getAvailablePermissionsForManagers(): array {
        return Permissions::getAvailablePermissionsForManagers();
    }

    /**
     * Get all available roles for an organization.
     */
    public static function getAvailableRoles(?int $orgId = null): array {
        $roles = [];

        $systemRoles = Role::systemRoles()->get();
        foreach ($systemRoles as $role) {
            $roles[] = [
                'slug'        => $role->slug,
                'title'       => $role->title,
                'description' => $role->description,
                'type'        => 'system',
                'is_system'   => true,
            ];
        }

        if ($orgId) {
            $customRoles = Role::customRoles()->forOrganization($orgId)->get();
            foreach ($customRoles as $role) {
                $roles[] = [
                    'slug'        => $role->slug,
                    'title'       => $role->title,
                    'description' => $role->description,
                    'type'        => 'custom',
                    'is_system'   => false,
                ];
            }
        }

        return $roles;
    }

    /**
     * Get available system roles for user assignment.
     */
    public static function getAvailableSystemRoles(): array {
        return [
            'super_admin' => self::$systemRoles['super_admin']['title'],
            'manager'     => self::$systemRoles['manager']['title'],
            'employee'    => self::$systemRoles['employee']['title'],
        ];
    }

    /**
     * Get a current authenticated user.
     */
    public static function getCurrentUser(): ?User {
        return Auth::guard('api')->user() ?? Auth::user();
    }

    /**
     * Get permissions that are always forbidden for managers.
     */
    public static function getRequiredForbiddenActionsForManagers(): array {
        return Permissions::getRequiredForbiddenKeys();
    }

    /**
     * Get permissions that must always be forbidden.
     */
    public static function getRequiredForbiddenPermissions(): array {
        return Permissions::getRequiredForbiddenPermissions();
    }

    /**
     * Get a resource name from a model.
     */
    public static function getResourceFromModel(Model $model): string {
        $className = class_basename($model);

        return mb_strtolower($className) . 's';
    }

    /**
     * Get forbidden actions for a role.
     */
    public static function getRoleForbiddenActions(string $roleSlug): array {
        if (self::isSystemRole($roleSlug)) {
            return Permissions::getSystemRoleForbiddenPermissions($roleSlug);
        }

        $role = Role::where('slug', $roleSlug)->where('is_system', false)->first();

        return $role ? $role->getForbidden() : [];
    }

    /**
     * Get system role definition.
     */
    public static function getSystemRole(string $roleSlug): ?array {
        return self::$systemRoles[$roleSlug] ?? null;
    }

    /**
     * Get all system role definitions.
     */
    public static function getSystemRoles(): array {
        return self::$systemRoles;
    }

    /**
     * System scope means acting outside of tenant/org restrictions.
     */
    public static function inSystemScope(?User $user = null): bool {
        $user = $user ?? self::getCurrentUser();

        if (! $user) {
            return false;
        }

        return self::isSuperAdmin($user) || $user->org_id === null;
    }

    /**
     * Check if action is forbidden for the user's role.
     */
    public static function isForbidden(string $action, string $resource, User $user, $targetModel = null): bool {
        if (self::isSuperAdmin($user)) {
            return false;
        }

        if (! $user->role) {
            return true;
        }

        $roleSlug = $user->role->slug;

        $actionsToCheck = [
            "{$resource}.{$action}",
            "{$resource}.{$action}.others",
            "{$resource}.{$action}." . ($targetModel ? self::getTargetType($targetModel, $user) : ''),
        ];

        $forbiddenActions = self::getRoleForbiddenActions($roleSlug);

        foreach ($actionsToCheck as $actionPattern) {
            if (in_array($actionPattern, $forbiddenActions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user is super admin.
     */
    public static function isSuperAdmin(?User $user = null): bool {
        $user = $user ?? self::getCurrentUser();

        return $user && $user->role && $user->role->slug === 'super_admin';
    }

    /**
     * Check if a role is a system role.
     */
    public static function isSystemRole(string $roleSlug): bool {
        return isset(self::$systemRoles[$roleSlug]);
    }

    /**
     * Check if a role exists.
     */
    public static function roleExists(string $roleSlug, ?int $orgId = null): bool {
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
     * Check if you should skip authorization.
     */
    public static function shouldSkipAuthorization(): bool {
        return app()->runningInConsole() && app()->environment(['local', 'testing']);
    }

    /**
     * Validate a custom role for manager restrictions.
     */
    public static function validateCustomRoleForManager(array $forbiddenActions): array {
        $errors            = [];
        $requiredForbidden = self::getRequiredForbiddenActionsForManagers();

        foreach ($requiredForbidden as $required) {
            if (! in_array($required, $forbiddenActions, true)) {
                $errors[] = "Custom role must forbid: {$required}";
            }
        }

        return $errors;
    }

    /**
     * Validate custom role permissions.
     */
    public static function validateCustomRolePermissions(array $forbiddenActions): array {
        return self::validateCustomRoleForManager($forbiddenActions);
    }

    /**
     * Get a target type for contextual permissions.
     */
    private static function getTargetType($targetModel, User $user): string {
        if (! $targetModel) {
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
     * Check business rule access.
     */
    private static function hasBusinessRuleAccess(string $action, string $resource, $targetModel, User $user): bool {
        if ($resource === 'users' && $action === 'delete' && $targetModel && $targetModel->id === $user->id) {
            return false;
        }

        // Only managers (of their org) and super admins can list/view licenses
        if ($resource === 'licenses' && in_array($action, ['view', 'list'], true)) {
            if (self::isSuperAdmin($user)) {
                return true;
            }

            return method_exists($user, 'isManager') && $user->isManager();
        }

        return true;
    }

    /**
     * Check license access.
     */
    private static function hasLicenseAccess($targetLicense, User $user): bool {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        return isset($targetLicense->org_id) && $targetLicense->org_id === $user->org_id;
    }

    /**
     * Check organization access rules.
     * Note: RLS handles data isolation, so we only check special cases here.
     */
    private static function hasOrganizationAccess(string $resource, $targetModel, User $user): bool {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        if (! $targetModel || ! method_exists($targetModel, 'getTable')) {
            return true;
        }

        // Special cases that need manual handling
        if ($resource === 'users') {
            return self::hasUserVisibilityAccess($targetModel, $user);
        }

        if ($resource === 'organizations') {
            return self::hasOrganizationManagementAccess($targetModel, $user);
        }

        if ($resource === 'roles') {
            return true;
        }

        if ($resource === 'licenses') {
            return self::hasLicenseAccess($targetModel, $user);
        }

        // For all other resources, RLS handles organization isolation,
        // so we return true and let RLS do the filtering
        return true;
    }

    /**
     * Check organization management access.
     */
    private static function hasOrganizationManagementAccess($targetOrg, User $user): bool {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        return isset($targetOrg->id) && $targetOrg->id === $user->org_id;
    }

    /**
     * Check user visibility access.
     */
    private static function hasUserVisibilityAccess(User $targetUser, User $currentUser): bool {
        if (self::isSuperAdmin($currentUser)) {
            return true;
        }

        if ($targetUser->id === $currentUser->id) {
            return true;
        }

        return $currentUser->isManager() && $targetUser->org_id === $currentUser->org_id;
    }
}
