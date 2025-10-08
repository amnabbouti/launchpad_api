<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function in_array;

class TenancyPolicyService
{
    /**
     * Clear the RLS context
     */
    public function clearContext(): void
    {
        $connection = DB::connection();
        $connection->statement('RESET app.org_id');
    }

    /**
     * Drop all RLS policies
     */
    public function dropAllPolicies(): void
    {
        $policies = config('tenancy.policies');

        foreach ($policies as $table => $tablePolicies) {
            DB::statement("DROP POLICY IF EXISTS \"{$table}_policy\" ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }

    /**
     * Set the RLS context for a user
     */
    public function setContext(User $user): void
    {
        $connection = DB::connection();

        if (! \App\Services\AuthorizationHelper::inSystemScope($user)) {
            // Regular user - set their org_id for RLS filtering
            $connection->statement("SET app.org_id = '{$user->org_id}'");
            Log::info('RLS context set for regular user', [
                'user_id'        => $user->id,
                'org_id'         => $user->org_id,
                'is_super_admin' => $user->is_super_admin,
            ]);
        } else {
            // Super admin or system user - allow access to all data
            $connection->statement('RESET app.org_id');
            Log::info('RLS context set for super admin', [
                'user_id'        => $user->id,
                'org_id'         => null,
                'is_super_admin' => $user->is_super_admin,
            ]);
        }
    }

    /**
     * Set up all RLS policies based on configuration
     */
    public function setupAllPolicies(): void
    {
        $policies = config('tenancy.policies');

        foreach ($policies as $table => $tablePolicies) {
            $this->setupTablePolicies($table, $tablePolicies);
        }
    }

    /**
     * Create a simple org-based policy
     */
    private function createSimplePolicy(string $table): void
    {
        $systemTables = [
            'api_key_usage',
            'api_key_rate_limits',
            'migrations',
            'cache',
            'cache_locks',
            'failed_jobs',
            'jobs',
            'job_batches',
            'sessions',
            'personal_access_tokens',
            'password_reset_tokens',
        ];

        if (in_array($table, $systemTables, true)) {
            DB::statement("
                CREATE POLICY \"{$table}_policy\" ON {$table}
                FOR ALL
                USING (true)
                WITH CHECK (true)
            ");

            return;
        }

        if ($table === 'users') {
            DB::statement("
                CREATE POLICY \"{$table}_policy\" ON {$table}
                FOR ALL
                USING (
                    org_id = current_setting('app.org_id')::uuid
                    OR
                    current_setting('app.org_id', true) IS NULL
                    OR
                    current_setting('app.org_id') = '00000000-0000-0000-0000-000000000000'
                    OR
                    org_id IS NULL
                )
                WITH CHECK (
                    org_id = current_setting('app.org_id')::uuid
                    OR
                    current_setting('app.org_id', true) IS NULL
                    OR
                    current_setting('app.org_id') = '00000000-0000-0000-0000-000000000000'
                )
            ");

            return;
        }

        $hasOrgId = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.columns
            WHERE table_name = ? AND column_name = 'org_id'
        ", [$table])[0]->count > 0;

        if ($hasOrgId) {
            DB::statement("
                CREATE POLICY \"{$table}_policy\" ON {$table}
                FOR ALL
                USING (
                    org_id = current_setting('app.org_id')::uuid
                    OR
                    current_setting('app.org_id') = '00000000-0000-0000-0000-000000000000'
                )
                WITH CHECK (
                    org_id = current_setting('app.org_id')::uuid
                    OR
                    current_setting('app.org_id') = '00000000-0000-0000-0000-000000000000'
                )
            ");
        } else {
            DB::statement("
                CREATE POLICY \"{$table}_policy\" ON {$table}
                FOR ALL
                USING (true)
                WITH CHECK (true)
            ");
        }
    }

    /**
     * Set up policies for a specific table
     */
    private function setupTablePolicies(string $table, array $policies): void
    {
        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
        $this->createSimplePolicy($table);
    }
}
