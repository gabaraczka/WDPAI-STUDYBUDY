-- Tabele

create table users (
    userid serial primary key,
    login text not null unique,
    password_hash text not null,
    email text,
    admin boolean default false,
    created_at timestamp default current_timestamp
);

create table folders (
    folderid serial primary key,
    userid integer references users(userid) on delete cascade,
    folder_name text not null
);

create table materials (
    materialid serial primary key,
    userid integer references users(userid) on delete cascade,
    folderid integer references folders(folderid) on delete set null,
    material text,
    material_name text not null,
    material_data bytea,
    material_path text
);

create table studycards (
    studycardid serial primary key,
    folderid integer references folders(folderid) on delete cascade,
    title text not null,
    content text,
    back_content text
);

create table studycards_log (
    logid serial primary key,
    studycardid integer references studycards(studycardid) on delete cascade,
    action text,
    timestamp timestamp default current_timestamp
);

-- Widok 1: view_materials_summary

create or replace view view_materials_summary as
select 
    m.materialid,
    m.material_name,
    f.folder_name,
    u.login
from materials m
join folders f on m.folderid = f.folderid
join users u on f.userid = u.userid;

-- Widok 2: view_studycards_info

create or replace view view_studycards_info as
select 
    s.studycardid,
    s.title,
    f.folder_name,
    u.login
from studycards s
join folders f on s.folderid = f.folderid
join users u on f.userid = u.userid;

-- Funkcja: count_user_cards

create or replace function count_user_cards(integer)
returns integer as $$
declare
    card_count integer;
begin
    select count(*) into card_count
    from studycards s
    join folders f on s.folderid = f.folderid
    where f.userid = $1;
    
    return card_count;
end;
$$ language plpgsql;

-- Wyzwalacz: trg_studycard_insert

create or replace function log_studycard_insert()
returns trigger as $$
begin
    insert into studycards_log(studycardid, action)
    values (new.studycardid, 'INSERT');
    return new;
end;
$$ language plpgsql;

create trigger trg_studycard_insert
after insert on studycards
for each row
execute function log_studycard_insert();


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
