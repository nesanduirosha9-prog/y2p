-- 103_24002275_create_instructors.sql
-- Timetable Officer (index 24002275).
-- Teaching assistants / instructors — a lighter record than a lecturer (no
-- department or email on the wireframe). Shown as green badges on the Courses
-- table and picked in the "Add / Edit Course" modal.

CREATE TABLE instructors (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(12)  NOT NULL UNIQUE,
    name       VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
