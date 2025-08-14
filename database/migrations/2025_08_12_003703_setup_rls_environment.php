<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void {
        // Drop the user (be careful with this in production!)
        DB::statement('DROP USER IF EXISTS app_user');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        // Only run this if we're using the app_user (non-superuser)
        if (config('database.connections.pgsql.username') === 'app_user') {
            // This migration will be skipped when using app_user
            // because app_user can't create users or alter database settings
            return;
        }

        // Create app_user if it doesn't exist
        DB::statement("DO \$\$ BEGIN IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'app_user') THEN CREATE USER app_user WITH PASSWORD 'app_password'; END IF; END \$\$;");

        // Grant privileges
        DB::statement('GRANT CONNECT ON DATABASE ' . config('database.connections.pgsql.database') . ' TO app_user');
        DB::statement('GRANT USAGE ON SCHEMA public TO app_user');
        DB::statement('GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO app_user');
        DB::statement('GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO app_user');
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO app_user');
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON SEQUENCES TO app_user');
        DB::statement('GRANT CREATE ON DATABASE ' . config('database.connections.pgsql.database') . ' TO app_user');

        // Set up database parameter
        DB::statement('ALTER DATABASE ' . config('database.connections.pgsql.database') . " SET app.org_id = '00000000-0000-0000-0000-000000000000'");
    }
};
