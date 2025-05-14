<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('generate:token', function () {
    // Check if test user exists
    $user = User::where('email', 'test@example.com')->first();

    // Create user if it doesn't exist
    if (! $user) {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->info('Test user created with email: test@example.com and password: password');
    } else {
        $this->info('Using existing test user with email: test@example.com');
    }

    // Revoke existing tokens
    $user->tokens()->delete();

    // Generate new token
    $token = $user->createToken('api-token')->plainTextToken;

    $this->info('API Token generated successfully:');
    $this->line($token);
    $this->info('Use this token in Postman by adding an Authorization header:');
    $this->line('Authorization: Bearer '.$token);
})->purpose('Generate an API token for testing in Postman');
