<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class EnterpriseSystemSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $this->command->info('🚀 Setting up Enterprise System...');

        $this->createSystemRoles();
        $organizations = $this->createOrganizations();
        $this->createUsers($organizations);
        $this->command->info('✅ Enterprise System setup complete!');
    }

    /**
     * Create sample organizations with licenses.
     */
    private function createOrganizations(): array {
        $this->command->info('🏢 Creating sample organizations...');

        $organizationData = [
            [
                'name'        => 'TechCorp Solutions',
                'email'       => 'admin@techcorp.com',
                'telephone'   => '+1-555-0123',
                'street'      => '123 Tech Street',
                'city'        => 'Silicon Valley',
                'province'    => 'CA',
                'postal_code' => '94000',
            ],
            [
                'name'        => 'Manufacturing Inc',
                'email'       => 'info@manufacturing.com',
                'telephone'   => '+1-555-0456',
                'street'      => '456 Factory Road',
                'city'        => 'Industrial City',
                'province'    => 'TX',
                'postal_code' => '75000',
            ],
            [
                'name'        => 'Small Business LLC',
                'email'       => 'contact@smallbiz.com',
                'telephone'   => '+1-555-0789',
                'street'      => '789 Main Street',
                'city'        => 'Hometown',
                'province'    => 'OH',
                'postal_code' => '43000',
            ],
        ];

        $organizations = [];
        foreach ($organizationData as $data) {
            // Create organization
            $organization = Organization::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'        => $data['name'],
                    'telephone'   => $data['telephone'],
                    'street'      => $data['street'],
                    'city'        => $data['city'],
                    'province'    => $data['province'],
                    'postal_code' => $data['postal_code'],
                ],
            );

            $organizations[$data['name']] = $organization;

            $this->command->info("  ✓ {$data['name']} (No license assigned)");
        }

        return $organizations;
    }

    /**
     * Create system roles aligned with AuthorizationEngine.
     */
    private function createSystemRoles(): void {
        $this->command->info('📋 Creating system roles...');
        $systemRoles = AuthorizationHelper::getSystemRoles();
        foreach ($systemRoles as $slug => $roleData) {
            $forbiddenPermissions = \App\Constants\Permissions::getSystemRoleForbiddenPermissions($slug);
            
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

            $this->command->info("  ✓ {$roleData['title']} ({$slug})");
        }
    }

    /**
     * Create sample users for testing.
     */
    private function createUsers(array $organizations): void {
        $this->command->info('👥 Creating sample users...');

        // Get roles
        $superAdminRole = Role::where('slug', 'super_admin')->where('is_system', true)->first();
        $managerRole    = Role::where('slug', 'manager')->where('is_system', true)->first();
        $employeeRole   = Role::where('slug', 'employee')->where('is_system', true)->first();

        // Create system super admin (not tied to any organization)
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@launchpad.com'],
            [
                'first_name'        => 'System',
                'last_name'         => 'Administrator',
                'email'             => 'superadmin@launchpad.com',
                'password'          => Hash::make('SuperAdmin123!'),
                'role_id'           => $superAdminRole->id,
                'org_id'            => null, // Super admin doesn't belong to any organization
                'email_verified_at' => now(),
            ],
        );
        $this->command->info("  ✓ Super Admin: {$superAdmin->email}");

        // Create users for each organization
        foreach ($organizations as $orgName => $organization) {
            $manager = User::updateOrCreate(
                ['email' => 'manager@' . mb_strtolower(str_replace(' ', '', $orgName)) . '.com'],
                [
                    'first_name'        => 'Organization',
                    'last_name'         => 'Manager',
                    'email'             => 'manager@' . mb_strtolower(str_replace(' ', '', $orgName)) . '.com',
                    'password'          => Hash::make('Manager123!'),
                    'role_id'           => $managerRole->id,
                    'org_id'            => $organization->id,
                    'email_verified_at' => now(),
                ],
            );
            $this->command->info("  ✓ Manager: {$manager->email} ({$orgName})");

            // Create employees for each organization
            for ($i = 1; $i <= 2; ++$i) {
                $employee = User::updateOrCreate(
                    ['email' => "employee{$i}@" . mb_strtolower(str_replace(' ', '', $orgName)) . '.com'],
                    [
                        'first_name'        => 'Employee',
                        'last_name'         => "#{$i}",
                        'email'             => "employee{$i}@" . mb_strtolower(str_replace(' ', '', $orgName)) . '.com',
                        'password'          => Hash::make('Employee123!'),
                        'role_id'           => $employeeRole->id,
                        'org_id'            => $organization->id,
                        'email_verified_at' => now(),
                    ],
                );
                $this->command->info("  ✓ Employee: {$employee->email} ({$orgName})");
            }
        }

        // Display login credentials
        $this->displayLoginCredentials();
    }

    /**
     * Display login credentials for testing.
     */
    private function displayLoginCredentials(): void {
        $this->command->info('');
        $this->command->info('🔑 TEST LOGIN CREDENTIALS:');
        $this->command->info('========================');
        $this->command->info('Super Admin: superadmin@launchpad.com / SuperAdmin123!');
        $this->command->info('');
        $this->command->info('TechCorp Solutions (Business):');
        $this->command->info('  Manager: manager@techcorpsolutions.com / Manager123!');
        $this->command->info('  Employee: employee1@techcorpsolutions.com / Employee123!');
        $this->command->info('  Employee: employee2@techcorpsolutions.com / Employee123!');
        $this->command->info('');
        $this->command->info('Manufacturing Inc (Team):');
        $this->command->info('  Manager: manager@manufacturinginc.com / Manager123!');
        $this->command->info('  Employee: employee1@manufacturinginc.com / Employee123!');
        $this->command->info('  Employee: employee2@manufacturinginc.com / Employee123!');
        $this->command->info('');
        $this->command->info('Small Business LLC (Solo):');
        $this->command->info('  Manager: manager@smallbusinessllc.com / Manager123!');
        $this->command->info('');
    }
}
