-- 101_24002275_create_timetable_sessions.sql
-- Timetable Officer (index 24002275).
-- Replaces TimetableController::sessions() — one row per block on the weekly
-- grid. The "Schedule Course" flow will INSERT here; the grid renders by
-- filtering on department + semester + year_of_study.

CREATE TABLE timetable_sessions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    course_id      INT NOT NULL,
    department     ENUM('cs','is') NOT NULL,   -- denormalised from courses for fast filtering
    semester       TINYINT NOT NULL,           -- 1..2
    year_of_study  TINYINT NOT NULL,           -- 1..4
    day_of_week    ENUM('mon','tue','wed','thu','fri') NOT NULL,
    start_hour     TINYINT NOT NULL,           -- 24h clock, e.g. 8, 14
    duration_hours TINYINT NOT NULL DEFAULT 1,
    location       VARCHAR(60) NOT NULL,
    session_type   ENUM('lecture','tutorial','lab','practical') NOT NULL DEFAULT 'lecture',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessions_course
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_sessions_filter (department, semester, year_of_study)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
