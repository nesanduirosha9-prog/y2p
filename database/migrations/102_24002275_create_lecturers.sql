-- 102_24002275_create_lecturers.sql
-- Timetable Officer (index 24002275).
-- Teaching staff shown on the "Lecturer Details" screen and picked as course
-- lecturers in the "Add / Edit Course" modal. `code` is the short badge label
-- (e.g. DSC) used across the UI.

CREATE TABLE lecturers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(12)  NOT NULL UNIQUE,
    name       VARCHAR(120) NOT NULL,
    department VARCHAR(80)  NOT NULL,
    email      VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
