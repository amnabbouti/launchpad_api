<?php

namespace App\Constants;

class Permissions
{
    /**
     * All available permissions that managers can grant or forbid to users.
     */
    public const AVAILABLE_PERMISSIONS = [
        // Inventory Management
        'items.view' => 'View items',
        'items.create' => 'Create items',
        'items.update' => 'Update items',
        'items.delete' => 'Delete items',

        'categories.view' => 'View categories',
        'categories.create' => 'Create categories',
        'categories.update' => 'Update categories',
        'categories.delete' => 'Delete categories',

        'locations.view' => 'View locations',
        'locations.create' => 'Create locations',
        'locations.update' => 'Update locations',
        'locations.delete' => 'Delete locations',

        'suppliers.view' => 'View suppliers',
        'suppliers.create' => 'Create suppliers',
        'suppliers.update' => 'Update suppliers',
        'suppliers.delete' => 'Delete suppliers',

        'stocks.view' => 'View stocks',
        'stocks.create' => 'Create stocks',
        'stocks.update' => 'Update stocks',
        'stocks.delete' => 'Delete stocks',

        // Maintenance Operations
        'maintenances.view' => 'View maintenances',
        'maintenances.create' => 'Create maintenances',
        'maintenances.update' => 'Update maintenances',
        'maintenances.delete' => 'Delete maintenances',

        'maintenancecategories.view' => 'View maintenance categories',
        'maintenancecategories.create' => 'Create maintenance categories',
        'maintenancecategories.update' => 'Update maintenance categories',
        'maintenancecategories.delete' => 'Delete maintenance categories',

        'maintenanceconditions.view' => 'View maintenance conditions',
        'maintenanceconditions.create' => 'Create maintenance conditions',
        'maintenanceconditions.update' => 'Update maintenance conditions',
        'maintenanceconditions.delete' => 'Delete maintenance conditions',

        // Check-in/out Operations
        'checkinouts.view' => 'View check-ins/outs',
        'checkinouts.create' => 'Create check-ins/outs',
        'checkinouts.update' => 'Update check-ins/outs',
        'checkinouts.delete' => 'Delete check-ins/outs',

        // Limited User Management
        'users.view' => 'View users in organization',
        'users.update.self' => 'Update own profile',

        // Reporting & Analytics
        'reports.view' => 'View reports',
        'reports.generate' => 'Generate reports',
        'analytics.view' => 'View analytics',

        // Plan Management
        'plans.view' => 'View available plans',
        'licenses.view' => 'View organization licenses',
    ];

    /**
     * Permissions that managers CANNOT grant (must always be forbidden in custom roles).
     */
    public const MANAGER_FORBIDDEN_PERMISSIONS = [
        'users.delete.self' => 'Delete themselves',
        'organizations.delete' => 'Delete organization',
        'users.promote.manager' => 'Promote to manager',
        'users.promote.super_admin' => 'Promote to super admin',
        'users.delete.super_admin' => 'Delete super admins',
        'users.delete.manager' => 'Delete managers',
        'organizations.create' => 'Create new organizations',
        'organizations.billing' => 'Access billing',
        'plans.create' => 'Create new plans',
        'plans.update' => 'Update plan definitions',
        'plans.delete' => 'Delete plans',
        'licenses.create' => 'Create licenses',
        'licenses.update' => 'Update licenses',
        'licenses.delete' => 'Delete licenses',
        'roles.system.modify' => 'Modify system roles',
    ];

    /**
     * Permissions forbidden for employees (includes all manager restrictions plus additional ones).
     */
    public const EMPLOYEE_FORBIDDEN_PERMISSIONS = [
        'users.delete.self' => 'Delete themselves',
        'organizations.delete' => 'Delete organization',
        'users.create' => 'Create users',
        'users.update.others' => 'Update other users',
        'users.delete' => 'Delete users',
        'users.promote.manager' => 'Promote to manager',
        'users.promote.super_admin' => 'Promote to super admin',
        'users.delete.super_admin' => 'Delete super admins',
        'users.delete.manager' => 'Delete managers',
        'organizations.create' => 'Create new organizations',
        'organizations.update' => 'Update organization',
        'organizations.billing' => 'Access billing',
        'roles.create' => 'Create roles',
        'roles.update' => 'Update roles',
        'roles.delete' => 'Delete roles',
        'roles.system.modify' => 'Modify system roles',
        'plans.view' => 'View plans',
        'plans.create' => 'Create new plans',
        'plans.update' => 'Update plan definitions',
        'plans.delete' => 'Delete plans',
        'licenses.view' => 'View licenses',
        'licenses.create' => 'Create licenses',
        'licenses.update' => 'Update licenses',
        'licenses.delete' => 'Delete licenses',
    ];

    /**
     * Get all permissions that managers can choose from when creating custom roles.
     */
    public static function getAvailablePermissionsForManagers(): array
    {
        return self::AVAILABLE_PERMISSIONS;
    }

    /**
     * Get permissions that must always be forbidden in manager-created roles.
     */
    public static function getRequiredForbiddenPermissions(): array
    {
        return self::MANAGER_FORBIDDEN_PERMISSIONS;
    }

    /**
     * Get just the permission keys (without descriptions).
     */
    public static function getAvailablePermissionKeys(): array
    {
        return array_keys(self::AVAILABLE_PERMISSIONS);
    }

    /**
     * Get just the forbidden permission keys (without descriptions).
     */
    public static function getRequiredForbiddenKeys(): array
    {
        return array_keys(self::MANAGER_FORBIDDEN_PERMISSIONS);
    }

    /**
     * Get forbidden permissions for a specific system role.
     */
    public static function getSystemRoleForbiddenPermissions(string $roleSlug): array
    {
        return match ($roleSlug) {
            'super_admin' => [],
            'manager' => array_keys(self::MANAGER_FORBIDDEN_PERMISSIONS),
            'employee' => array_keys(self::EMPLOYEE_FORBIDDEN_PERMISSIONS),
            default => [],
        };
    }

    /**
     * Get forbidden permissions for manager role.
     */
    public static function getManagerForbiddenPermissions(): array
    {
        return array_keys(self::MANAGER_FORBIDDEN_PERMISSIONS);
    }

    /**
     * Get forbidden permissions for employee role.
     */
    public static function getEmployeeForbiddenPermissions(): array
    {
        return array_keys(self::EMPLOYEE_FORBIDDEN_PERMISSIONS);
    }
}
