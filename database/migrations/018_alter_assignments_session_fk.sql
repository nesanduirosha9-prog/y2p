-- 018_alter_assignments_session_fk.sql
-- Adds ON UPDATE CASCADE to the assignments -> timetable_sessions FK, so the
-- Timetable Officer can move a session (change its room/day/start hour, which
-- is its primary key — see 005) without the session's weekly assignments
-- blocking the UPDATE with an FK error. ON DELETE CASCADE is unchanged.
--
-- The constraint gets a new name: dropping and re-adding an FK under the same
-- name in one ALTER TABLE is unreliable across MySQL/MariaDB versions.
ALTER TABLE assignments
    DROP FOREIGN KEY fk_assignments_session,
    ADD CONSTRAINT fk_assignments_session_cascade
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE ON UPDATE CASCADE;
