<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class CreateUserToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user-token {email? : The email of the user} {--name=expo-app : The token name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an encrypted access token for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $tokenName = $this->option('name');

        // If no email provided, ask for it or show available users
        if (! $email) {
            $users = User::select('email', 'first_name', 'last_name')->get();

            if ($users->isEmpty()) {
                $this->error('No users found in the database.');

                return;
            }

            $this->info('Available users:');
            foreach ($users as $user) {
                $this->line("- {$user->email} ({$user->first_name} {$user->last_name})");
            }

            $email = $this->ask('Enter the email of the user');
        }

        // Find the user
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found.");

            return;
        }

        // Create a new token for the user
        $token = $user->createToken($tokenName)->plainTextToken;

        // Encrypt the token (same as in AuthController)
        $encryptedToken = Crypt::encryptString($token);

        $this->info("Token created successfully for {$user->first_name} {$user->last_name} ({$user->email})");
        $this->line('');
        $this->line('Encrypted Token:');
        $this->line($encryptedToken);
        $this->line('');
        $this->line('Plain Token (for development/testing):');
        $this->line($token);
        $this->line('');
        $this->warn('Keep this token secure and do not share it!');
    }
}
