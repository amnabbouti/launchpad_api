<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->json('forbidden')->nullable();
            $table->timestamps();
        });

        // essential roles that are required for my system to function
        $now = now();
        DB::table('roles')->insert([
            [
                'slug' => 'super_admin',
                'title' => 'Super Administrator',
                'forbidden' => json_encode([]), 
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'manager',
                'title' => 'Manager',
                'forbidden' => json_encode([
                    'roles.create',
                    'roles.update', 
                    'roles.delete',
                    'organizations.create',
                    'organizations.update',
                    'organizations.delete',
                    'users.edit.role_self', 
                ]), 
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'employee',
                'title' => 'Employee',
                'forbidden' => json_encode([
                    'users.create',
                    'users.edit',
                    'users.delete',
                    'roles.create',
                    'roles.update',
                    'roles.delete',
                    'organizations.create',
                    'organizations.update',
                    'organizations.delete',
                ]), 
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
