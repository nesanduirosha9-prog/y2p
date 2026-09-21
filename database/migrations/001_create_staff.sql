-- 001_create_staff.sql
-- Schema rewrite from the EER diagram (docs/eer_diagram.drawio).
-- Merges the old `users` (login), `lecturers` (catalog), and `instructors`
-- (catalog) tables into the EER's single `Staff` entity. Previously a login
-- account and its catalog row were two disconnected tables patched together
-- with an FK (migration 109, now removed) — this fixes that at the root:
-- there is exactly one row per person, and it is both their login and their
-- academic-staff record.
--
-- Primary key is `code` (the existing 3-letter badge, e.g. DSC/MKA), not an
-- auto-increment id — it's already unique per person and is the natural key
-- used everywhere in the UI. `email` stays UNIQUE for login lookups.
--
-- The EER's ISA hierarchy (Staff -> {Academic Staff, Timetable Officer};
-- Academic Staff -> {Junior Staff, Senior Lecturer}; Junior Staff ->
-- Coordinator; Senior Lecturer -> In-Charge) is flattened into two columns
-- instead of five tables:
--   - `academic_rank` is the base rank (junior/senior), required only for
--     academic_staff.
--   - `position` is an ADDITIVE extra role on top of that rank, not a
--     replacement: a Coordinator is still academic_rank='junior' (so every
--     "junior staff can do X" check keeps matching them), and an In-Charge
--     person is still academic_rank='senior'. 'coordinator' is only valid
--     with rank 'junior'; 'in_charge' is only valid with rank 'senior'.
-- The two CHECK constraints below enforce this; mirror the same rule in
-- app-layer validation too, since CHECK is only enforced on MySQL 8.0.16+ /
-- MariaDB 10.2+ and is silently ignored on older servers.

CREATE TABLE staff (
    code                 VARCHAR(12)  NOT NULL PRIMARY KEY,
    email                VARCHAR(255) NOT NULL UNIQUE,   -- EER: University Email; also the login username
    password             VARCHAR(255) NOT NULL,          -- EER: Hashed Passkey (bcrypt hash)
    name                 VARCHAR(120) NOT NULL,
    role                 ENUM('timetable_officer', 'academic_staff') NOT NULL,
    academic_rank        ENUM('junior', 'senior') NULL,
    position             ENUM('coordinator', 'in_charge') NULL,
    department           VARCHAR(80)  NULL,
    designation          VARCHAR(80)  NULL,              -- EER: Profile Data
    office               VARCHAR(80)  NULL,               -- EER: Profile Data
    extension            VARCHAR(10)  NULL,               -- EER: Profile Data
    bio                  TEXT         NULL,               -- EER: Profile Data
    availability_status  ENUM('available', 'unavailable', 'on_leave') NOT NULL DEFAULT 'available',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_staff_rank_matches_role CHECK (
        (role = 'academic_staff' AND academic_rank IS NOT NULL) OR
        (role = 'timetable_officer' AND academic_rank IS NULL)
    ),
    CONSTRAINT chk_staff_position_matches_rank CHECK (
        position IS NULL OR
        (position = 'coordinator' AND academic_rank = 'junior') OR
        (position = 'in_charge' AND academic_rank = 'senior')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
