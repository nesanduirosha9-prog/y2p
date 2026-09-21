-- 006_create_assignments.sql
-- EER `Assignment` entity — a weekly topic/plan row for a recurring session
-- (e.g. "Week 3: Introduction to Recursion"). The original EER modeled
-- `Includes` as a 1:1 between Timetable Session and Assignment, which is
-- too restrictive for a recurring weekly slot; corrected here to 1:N, one
-- Assignment row per week of term. Key extends the session's own composite
-- key with `week_num`.

CREATE TABLE assignments (
    room_code   VARCHAR(20) NOT NULL,
    day_of_week ENUM('mon','tue','wed','thu','fri') NOT NULL,
    start_hour  TINYINT NOT NULL,
    week_num    TINYINT NOT NULL,
    title       VARCHAR(150) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (room_code, day_of_week, start_hour, week_num),
    CONSTRAINT fk_assignments_session
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
