# Database

The schema lives here as ordered SQL files. There is **one shared schema** in
git; each person runs it against **their own local `staffsync_db`**. Nobody
keeps a private database or a private copy of the schema.

## First-time setup

```bash
cp config.php.example config.php     # then edit if your MySQL isn't root / no-password
sudo /opt/lampp/lampp startmysql     # XAMPP MySQL
php database/migrate.php --seed       # build the schema + load sample data
```

`php` above is your CLI PHP; if it isn't on PATH use `/opt/lampp/bin/php`.

## Everyday use

| Command | What it does |
|---|---|
| `php database/migrate.php` | Apply migrations that haven't run yet |
| `php database/migrate.php --status` | List every migration as `[x]` applied / `[ ]` pending |
| `php database/migrate.php --seed` | Apply pending migrations, then re-run all seeds |
| `php database/migrate.php --fresh --seed` | Drop the whole database and rebuild from zero |

**After every `git pull`, run `php database/migrate.php`** — a teammate may have
added a table.

The app itself (`app/core/Database.php`) no longer creates any tables. If you
skip the migrate step, the first page that touches the DB will tell you to run it.

## How it works

- Files in `migrations/` run in **filename order**.
- Each applied file is recorded in a `schema_migrations` table, so re-running
  `migrate.php` only applies what's new.
- `seeds/` files are sample data. They're written to be **re-runnable**
  (upsert or clear-then-insert) and only run with `--seed` or `--fresh`.

## Naming convention

```
migrations/<NNN>_<owner>_<description>.sql
seeds/<NNN>_<owner>_<description>.sql
```

- `<NNN>` — 3-digit number that sets run order. Use **your assigned range** so two
  people never pick the same number.
- `<owner>` — your student index, or `common` for shared tables.
- `<description>` — `snake_case`, e.g. `create_courses`, `add_room_to_sessions`.

### Range assignments

| Range | Owner | Area |
|---|---|---|
| `000`–`099` | `common` | Shared tables every role needs (`users`, …). Change only by team agreement. |
| `100`–`199` | `24002275` | Timetable Officer — courses, timetable sessions |
| `200`–`299` | _(unassigned)_ | |
| `300`–`399` | _(unassigned)_ | |
| `400`–`499` | _(unassigned)_ | |

Fill in your index and area when you take a range.

## Rules

1. **Never edit a migration once it's pushed/merged.** Someone has already run
   it — your edit won't re-apply. To change a table, add a **new** migration
   with `ALTER TABLE`.
2. **One change per file.** One `CREATE TABLE`, or one set of related `ALTER`s.
3. **Common tables are shared.** Discuss `0xx_common_*` changes with everyone
   before merging — they affect all four features.
4. Migrations use plain `CREATE TABLE` / `ALTER TABLE` (no `IF NOT EXISTS`) —
   the `schema_migrations` tracking table already stops double-runs, and a hard
   failure on a repeat is a useful signal.

## Current tables

| Table | Migration | Owner |
|---|---|---|
| `users` | `001_common_create_users.sql` | common |
| `courses` | `100` + `104` (adds `credits`, drops `lecturer_name`) | 24002275 |
| `timetable_sessions` | `101_24002275_create_timetable_sessions.sql` | 24002275 |
| `lecturers` | `102_24002275_create_lecturers.sql` | 24002275 |
| `instructors` | `103_24002275_create_instructors.sql` | 24002275 |
| `course_lecturers` | `105_24002275_create_course_lecturers.sql` | 24002275 |
| `course_instructors` | `106_24002275_create_course_instructors.sql` | 24002275 |
| `notifications` | `107_24002275_create_notifications.sql` | 24002275 |

Every Timetable Officer screen now reads from these tables — there is no
hardcoded sample data in the controllers. The screens render fine against an
empty database; `--seed` loads the sample rows. The "Schedule Course" /
"Add Course" write paths are still client-side only (no `INSERT` yet).

## Seeing the data

XAMPP bundles phpMyAdmin: <http://localhost/phpmyadmin> → `staffsync_db`.
CLI: `/opt/lampp/bin/mysql -u root staffsync_db`
