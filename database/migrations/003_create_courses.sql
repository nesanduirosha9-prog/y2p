-- 003_create_courses.sql
-- Same shape as the original `courses` table, now keyed by `code` (e.g.
-- CS1101) instead of a surrogate auto-increment id — `code` was already the
-- de facto key every query used, this just makes it official and drops the
-- redundant id column.

CREATE TABLE courses (
    code          VARCHAR(20)  NOT NULL PRIMARY KEY,
    title         VARCHAR(150) NOT NULL,
    credits       TINYINT NOT NULL DEFAULT 3,
    department    ENUM('cs','is') NOT NULL,
    year_of_study TINYINT NOT NULL,               -- 1..4
    semester      TINYINT NOT NULL,               -- 1..2
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_courses_filter (department, year_of_study, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
