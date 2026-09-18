-- 106_24002275_create_course_instructors.sql
-- Timetable Officer (index 24002275).
-- Many-to-many: which instructors (TAs) are assigned to a course.

CREATE TABLE course_instructors (
    course_id     INT NOT NULL,
    instructor_id INT NOT NULL,
    PRIMARY KEY (course_id, instructor_id),
    CONSTRAINT fk_ci_course     FOREIGN KEY (course_id)     REFERENCES courses(id)     ON DELETE CASCADE,
    CONSTRAINT fk_ci_instructor FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
