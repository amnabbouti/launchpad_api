<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Constants\Permissions;
use App\Models\Role;
use App\Services\AuthorizationHelper;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $systemRoles = AuthorizationHelper::getSystemRoles();

        foreach ($systemRoles as $slug => $roleData) {
            $forbiddenPermissions = Permissions::getSystemRoleForbiddenPermissions($slug);

            Role::updateOrCreate(
                ['slug' => $slug, 'is_system' => true],
                [
                    'title'       => $roleData['title'],
                    'description' => $roleData['description'],
                    'forbidden'   => $forbiddenPermissions,
                    'org_id'      => null,
                    'is_system'   => true,
                ],
            );
        }
    }
}
