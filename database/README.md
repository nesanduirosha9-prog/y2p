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
migrations/<NNN>_create_<table>.sql
seeds/<NNN>_<table>.sql
```

The schema was rebuilt from scratch to match `docs/eer_diagram.drawio` (see
that file's revision notes for why) — every table now gets one migration and
one seed file, numbered in dependency order (`001` has no foreign keys into
anything else; every later file only references tables numbered before it).
The old per-owner index-range convention (`<NNN>_<owner>_<description>.sql`)
no longer applies now that the whole schema is a single coordinated design;
a new table still just takes the next free number.

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

No table uses an auto-increment id — every primary key is either a real
natural/business key (`staff.code`, `courses.code`, `rooms.code`, a
timetable slot's `(room_code, day_of_week, start_hour)`) or, where no
attribute is genuinely unique, a generated UUID. See each migration file's
header comment for the reasoning behind its specific key.

| Table | Migration | Notes |
|---|---|---|
| `staff` | `001_create_staff.sql` | Merges the old `users`+`lecturers`+`instructors`; PK `code` |
| `rooms` | `002_create_rooms.sql` | PK `code` |
| `courses` | `003_create_courses.sql` | PK `code` |
| `course_staff` | `004_create_course_staff.sql` | Replaces `course_lecturers`+`course_instructors`; `assignment_role` per course |
| `timetable_sessions` | `005_create_timetable_sessions.sql` | PK `(room_code, day_of_week, start_hour)` — makes double-booking impossible |
| `assignments` | `006_create_assignments.sql` | Weekly topic per session |
| `notifications` | `007_create_notifications.sql` | PK is a UUID; no `is_read` here |
| `notification_recipients` | `008_create_notification_recipients.sql` | Per-recipient `is_read` |
| `leave_requests` | `009_create_leave_requests.sql` | PK is a UUID |
| `workload_tasks` | `010_create_workload_tasks.sql` | PK is a UUID |
| `chat_rooms` | `011_create_chat_rooms.sql` | PK is a UUID; scoped to a course |
| `chat_participants` | `012_create_chat_participants.sql` | Chat membership |
| `messages` | `013_create_messages.sql` | PK is a UUID |
| `reschedule_requests` | `014_create_reschedule_requests.sql` | PK is a UUID |
| `support_requests` | `015_create_support_requests.sql` | PK is a UUID |

Every Timetable Officer screen reads from these tables — there is no
hardcoded sample data in those controllers. The screens render fine against
an empty database; `--seed` loads the sample rows. The instructor-side
`leave`/`workload`/`messages`/`requests` pages still render hardcoded PHP
arrays (per `docs/DESIGN_PATTERNS_PLAN.md` item 8) — their tables exist now,
but wiring each page to a real Model is separate follow-up work.

## Seeded accounts

`seeds/001_staff.sql` loads one login per role/position so every dashboard
can be exercised without registering a new account. **Every seeded account
shares the same placeholder password: `Password123!`** (bcrypt hash baked
into the seed file — see its header comment).

`role`/`academic_rank` are the base identity; `position` is an **additive**
extra on top of `academic_staff` (a Coordinator is still `academic_rank =
'junior'`, an In-Charge is still `'senior'`) — see `001_create_staff.sql`'s
header for the full reasoning, and `016_alter_staff_registration.sql` for
the `status`/`phone` columns added for the registration-approval workflow.

| Role / position | Dashboard | Email | Name |
|---|---|---|---|
| Timetable Officer | `/timetable` | `tmo@ucsc.cmb.ac.lk` | T. M. Officer |
| Coordinator (`academic_staff`, junior + `position=coordinator`) | `/instructor/timetable` + **Staff** tab | `mka@ucsc.cmb.ac.lk` | Mr. Kwame Addo |
| In-Charge (`academic_staff`, senior + `position=in_charge`) | `/instructor/timetable` + **Staff** + **Accounts** tabs | `dsc@ucsc.cmb.ac.lk` | Dr. Sarah Chen |
| Lecturer / junior staff (`academic_staff`, junior, no position) | `/instructor/timetable` | `mad@`, `mab@`, `mat@`, `mko@`, `mem@`, `meq@`, `mna@`, `tmf@`, `myd@`, `myb@` `ucsc.cmb.ac.lk` | Ato Baidoo, Adom Boateng, Atta Tetteh, Kojo Amoah, Efua Mensah, Esi Quaye, Nana Ama, Thilini Fernando, Yaa Darko, Yaw Bediako |
| Senior Lecturer (`academic_staff`, senior, no position) | `/instructor/timetable` | `dad@`, `dep@`, `dfa@`, `dka@`, `dlo@`, `dlw@`, `pdn@`, `pjo@`, `pka@`, `prm@` `ucsc.cmb.ac.lk` | Amara Diallo, Elena Petrov, Fatima Ahmed, Kofi Anning, Linda Osei, Liu Wei, David Nkrumah, James Osei, Kweku Asante, Richard Mensah |

New self-registrations via `/signup` are **not** in this table — they land
with `status = 'pending'` and no role until a Coordinator or In-Charge
approves them from `/coordinator/staff`.

## Seeing the data

XAMPP bundles phpMyAdmin: <http://localhost/phpmyadmin> → `staffsync_db`.
CLI: `/opt/lampp/bin/mysql -u root staffsync_db`
