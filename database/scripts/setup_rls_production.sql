-- Production RLS Setup Script
DO $$
BEGIN
    IF current_setting('APP_USER', true) IS NULL THEN
        RAISE EXCEPTION 'Please set APP_USER variable before running this script. Use: \set APP_USER ''your_username''';
    END IF;

    IF current_setting('APP_PASSWORD', true) IS NULL THEN
        RAISE EXCEPTION 'Please set APP_PASSWORD variable before running this script. Use: \set APP_PASSWORD ''your_password''';
    END IF;
END $$;

-- 1. Create application user
DO $$
DECLARE
    app_user_name TEXT := current_setting('APP_USER');
    app_password TEXT := current_setting('APP_PASSWORD');
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = app_user_name) THEN
        EXECUTE format('CREATE USER %I WITH PASSWORD %L', app_user_name, app_password);
        RAISE NOTICE 'Created user: %', app_user_name;
    ELSE
        EXECUTE format('ALTER USER %I WITH PASSWORD %L', app_user_name, app_password);
        RAISE NOTICE 'Updated password for existing user: %', app_user_name;
    END IF;
END $$;

-- 2. Grant privileges
DO $$
DECLARE
    app_user_name TEXT := current_setting('APP_USER');
BEGIN
    EXECUTE format('GRANT CONNECT ON DATABASE %I TO %I', current_database(), app_user_name);
    EXECUTE format('GRANT USAGE ON SCHEMA public TO %I', app_user_name);
    EXECUTE format('GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO %I', app_user_name);
    EXECUTE format('GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO %I', app_user_name);
    EXECUTE format('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO %I', app_user_name);
    EXECUTE format('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON SEQUENCES TO %I', app_user_name);
    EXECUTE format('GRANT CREATE ON DATABASE %I TO %I', current_database(), app_user_name);
    RAISE NOTICE 'Granted privileges to user: %', app_user_name;
END $$;

-- 3. Set database parameter
ALTER DATABASE current_database() SET app.org_id = '00000000-0000-0000-0000-000000000000';
RAISE NOTICE 'Set database parameter app.org_id';

-- 4. Enable RLS on all tables
DO $$
DECLARE
    r RECORD;
    table_count INTEGER := 0;
BEGIN
    FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = 'public')
    LOOP
        EXECUTE 'ALTER TABLE ' || quote_ident(r.tablename) || ' ENABLE ROW LEVEL SECURITY';
        EXECUTE 'ALTER TABLE ' || quote_ident(r.tablename) || ' FORCE ROW LEVEL SECURITY';
        table_count := table_count + 1;
    END LOOP;
    RAISE NOTICE 'Enabled RLS on % tables', table_count;
END $$;

-- 5. Create RLS policies for tables with org_id
DO $$
DECLARE
    r RECORD;
    has_org_id BOOLEAN;
    policy_count INTEGER := 0;
BEGIN
    FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = 'public')
    LOOP
        -- Check if table has org_id column
        SELECT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_name = r.tablename
            AND column_name = 'org_id'
        ) INTO has_org_id;

        IF has_org_id THEN
            -- Drop existing policy if exists
            EXECUTE 'DROP POLICY IF EXISTS ' || quote_ident(r.tablename || '_policy') || ' ON ' || quote_ident(r.tablename);

            -- Create new policy
            EXECUTE 'CREATE POLICY ' || quote_ident(r.tablename || '_policy') || ' ON ' || quote_ident(r.tablename) || '
                FOR ALL
                USING (
                    org_id = current_setting(''app.org_id'')::uuid
                    OR
                    current_setting(''app.org_id'') = ''00000000-0000-0000-0000-000000000000''
                )
                WITH CHECK (
                    org_id = current_setting(''app.org_id'')::uuid
                    OR
                    current_setting(''app.org_id'') = ''00000000-0000-0000-0000-000000000000''
                )';
            policy_count := policy_count + 1;
            RAISE NOTICE 'Created RLS policy for table: %', r.tablename;
        END IF;
    END LOOP;
    RAISE NOTICE 'Created % RLS policies for tables with org_id', policy_count;
END $$;

-- 6. Special handling for users table (if exists)
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'users') THEN
        -- Drop existing policy if exists
        DROP POLICY IF EXISTS users_policy ON users;

        -- Create hybrid policy for users table
        CREATE POLICY users_policy ON users
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
        );
        RAISE NOTICE 'Created special RLS policy for users table';
    END IF;
END $$;

-- 7. System tables (no org_id filtering)
DO $$
DECLARE
    system_tables TEXT[] := ARRAY[
        'api_key_usage', 'api_key_rate_limits', 'migrations', 'cache',
        'cache_locks', 'failed_jobs', 'jobs', 'job_batches', 'sessions',
        'personal_access_tokens', 'password_reset_tokens'
    ];
    table_name TEXT;
    system_policy_count INTEGER := 0;
BEGIN
    FOREACH table_name IN ARRAY system_tables
    LOOP
        IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = table_name) THEN
            EXECUTE 'DROP POLICY IF EXISTS ' || quote_ident(table_name || '_policy') || ' ON ' || quote_ident(table_name);
            EXECUTE 'CREATE POLICY ' || quote_ident(table_name || '_policy') || ' ON ' || quote_ident(table_name) || ' FOR ALL USING (true) WITH CHECK (true)';
            system_policy_count := system_policy_count + 1;
        END IF;
    END LOOP;
    RAISE NOTICE 'Created % RLS policies for system tables', system_policy_count;
END $$;

-- Success message
SELECT
    'RLS setup completed successfully!' as status,
    current_setting('APP_USER') as app_user,
    current_database() as database_name;
