-- 100_24002275_create_courses.sql
-- Timetable Officer (index 24002275).
-- Replaces TimetableController::catalog() — the course catalogue that fills the
-- "Course Module" dropdown in the Schedule Course modal, filtered by
-- department / year / semester.

CREATE TABLE courses (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(20)  NOT NULL UNIQUE,   -- e.g. CS1101
    title         VARCHAR(150) NOT NULL,
    department    ENUM('cs','is') NOT NULL,
    year_of_study TINYINT NOT NULL,               -- 1..4
    semester      TINYINT NOT NULL,               -- 1..2
    lecturer_name VARCHAR(120) NOT NULL,          -- denormalised for now;
                                                  -- FK to a lecturers table later
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_courses_filter (department, year_of_study, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
