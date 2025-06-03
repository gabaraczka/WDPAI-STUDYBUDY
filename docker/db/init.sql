GRANT ALL PRIVILEGES ON DATABASE db TO docker;

GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO docker;

GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO docker;

GRANT ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public TO docker;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'users' AND table_schema = 'public') THEN
        GRANT ALL PRIVILEGES ON TABLE public.users TO docker;
    ELSE
        RAISE NOTICE 'Table "users" does not exist yet. Privileges will be applied when the table is created.';
    END IF;
END $$;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'materials' AND table_schema = 'public') THEN
        GRANT ALL PRIVILEGES ON TABLE public.materials TO docker;
    ELSE
        RAISE NOTICE 'Table "materials" does not exist yet. Privileges will be applied when the table is created.';
    END IF;
END $$;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'folders' AND table_schema = 'public') THEN
        GRANT ALL PRIVILEGES ON TABLE public.folders TO docker;
    ELSE
        RAISE NOTICE 'Table "folders" does not exist yet. Privileges will be applied when the table is created.';
    END IF;
END $$;

ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO docker;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON SEQUENCES TO docker;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON FUNCTIONS TO docker;

CREATE OR REPLACE FUNCTION grant_users_table_privileges()
RETURNS event_trigger AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'users' AND table_schema = 'public') THEN
        EXECUTE 'GRANT ALL PRIVILEGES ON TABLE public.users TO docker';
    END IF;
END;
$$ LANGUAGE plpgsql;

DROP EVENT TRIGGER IF EXISTS ensure_users_privileges;

CREATE EVENT TRIGGER ensure_users_privileges
ON ddl_command_end
WHEN TAG IN ('CREATE TABLE', 'ALTER TABLE')
EXECUTE FUNCTION grant_users_table_privileges();
