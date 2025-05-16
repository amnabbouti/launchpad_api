<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create or get a default organization
        $org = \App\Models\Organization::firstOrCreate([
            'name' => 'Default Organization',
        ], [
            'email' => 'org@example.com',
            'telephone' => '000-000-0000',
            'address' => '123 Main St',
            'remarks' => null,
            'website' => null,
        ]);

        // Create the admin user with the correct organization_id
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'organization_id' => $org->id,
                'org_role' => 'org_admin',
                'email_verified_at' => now(),
            ]
        );

    }
}
