-- 104_24002275_alter_courses_add_credits.sql
-- Timetable Officer (index 24002275).
-- The Courses screen shows a credit value per course, and course→staff is now a
-- many-to-many relation (see 105 / 106) rather than the single denormalised
-- `lecturer_name` column from migration 100.

ALTER TABLE courses
    ADD COLUMN credits TINYINT NOT NULL DEFAULT 3 AFTER title;

ALTER TABLE courses
    DROP COLUMN lecturer_name;
