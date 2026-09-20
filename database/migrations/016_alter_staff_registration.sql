-- 016_alter_staff_registration.sql
-- Adds a real registration-approval workflow on top of the existing `staff`
-- table. Today AuthController::signup() creates a fully active row the
-- instant someone registers; the Coordinator "Staff" screen (pulled from
-- Figma) needs the opposite: a new signup sits unassigned/`pending` until a
-- Coordinator or In-Charge reviews it and assigns a role + rank via the
-- "Assign Role" dropdown, at which point the row becomes `active`.
--
-- `role`/`academic_rank` become nullable because a pending row has neither
-- yet — the existing `chk_staff_rank_matches_role` CHECK is redefined to
-- exempt `status = 'pending'` rows rather than dropped outright, so the
-- rank/role pairing is still enforced the moment a row goes active.
--
-- `phone` is new because the Pending Registration Requests / Active Staff
-- Members tables in Figma both show a phone column. The signup form itself
-- only collects email/password; a member fills in their name/phone later
-- from Settings once signed in.

-- Combined into a single ALTER TABLE statement — database/migrate.php runs
-- each migration file as one $pdo->exec() call with no statement-splitting,
-- so every migration so far is exactly one statement; this follows suit.
ALTER TABLE staff
    MODIFY COLUMN role          ENUM('timetable_officer', 'academic_staff') NULL,
    MODIFY COLUMN academic_rank ENUM('junior', 'senior') NULL,
    ADD COLUMN status ENUM('pending', 'active') NOT NULL DEFAULT 'active' AFTER availability_status,
    ADD COLUMN phone  VARCHAR(20) NULL AFTER designation,
    DROP CONSTRAINT chk_staff_rank_matches_role,
    ADD CONSTRAINT chk_staff_rank_matches_role CHECK (
        status = 'pending' OR
        (role = 'academic_staff' AND academic_rank IS NOT NULL) OR
        (role = 'timetable_officer' AND academic_rank IS NULL)
    );
