-- 108_alter_users_add_role.sql
-- Add role column to users table

ALTER TABLE users ADD COLUMN role ENUM('timetable_officer', 'instructor') NOT NULL DEFAULT 'timetable_officer';
