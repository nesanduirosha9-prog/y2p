-- 019_alter_reschedule_session_fk.sql
-- Same change as 018, for reschedule_requests -> timetable_sessions: a moved
-- session carries its pending reschedule requests with it instead of the
-- UPDATE failing. ON DELETE CASCADE is unchanged; new constraint name for the
-- same reason as 018.
ALTER TABLE reschedule_requests
    DROP FOREIGN KEY fk_reschedule_session,
    ADD CONSTRAINT fk_reschedule_session_cascade
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE ON UPDATE CASCADE;
