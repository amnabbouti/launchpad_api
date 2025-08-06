<?php

use App\Constants\Permissions;
use App\Services\AuthorizationEngine;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get system roles from AuthorizationEngine for basic info
        $systemRoles = AuthorizationEngine::getSystemRoles();

        foreach ($systemRoles as $slug => $roleData) {
            // Get actual forbidden permissions from Permissions constants
            $forbiddenPermissions = Permissions::getSystemRoleForbiddenPermissions($slug);

            DB::table('roles')
                ->where('slug', $slug)
                ->update([
                    'title' => $roleData['title'],
                    'description' => $roleData['description'],
                    'forbidden' => json_encode($forbiddenPermissions),
                    'org_id' => null,
                    'is_system' => true,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to a basic system roles state (this is rarely used but should be functional)
        $basicRoles = [
            'super_admin' => [
                'title' => 'Super Administrator',
                'description' => 'Full system access - can manage everything',
                'forbidden' => [],
                'is_system' => true,
            ],
            'manager' => [
                'title' => 'Manager',
                'description' => 'Organization manager',
                'forbidden' => [
                    'users.delete.self',
                    'organizations.delete',
                    'users.promote.super_admin',
                    'organizations.create',
                    'plans.create',
                    'plans.update',
                    'plans.delete',
                    'licenses.create',
                    'licenses.update',
                    'licenses.delete',
                ],
                'is_system' => true,
            ],
            'employee' => [
                'title' => 'Employee',
                'description' => 'Employee with basic access',
                'forbidden' => [
                    'users.delete.self',
                    'organizations.delete',
                    'users.create',
                    'users.update.others',
                    'users.delete',
                    'users.promote.super_admin',
                    'organizations.create',
                    'organizations.update',
                    'roles.create',
                    'roles.update',
                    'roles.delete',
                    'plans.view',
                    'plans.create',
                    'plans.update',
                    'plans.delete',
                    'licenses.view',
                    'licenses.create',
                    'licenses.update',
                    'licenses.delete',
                ],
                'is_system' => true,
            ],
        ];

        foreach ($basicRoles as $slug => $roleData) {
            DB::table('roles')
                ->where('slug', $slug)
                ->update([
                    'title' => $roleData['title'],
                    'description' => $roleData['description'],
                    'forbidden' => json_encode($roleData['forbidden']),
                    'is_system' => $roleData['is_system'],
                    'updated_at' => now(),
                ]);
        }
    }
};
