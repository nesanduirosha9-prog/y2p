-- 105_24002275_create_course_lecturers.sql
-- Timetable Officer (index 24002275).
-- Many-to-many: which lecturers teach a course. Replaces courses.lecturer_name.

CREATE TABLE course_lecturers (
    course_id   INT NOT NULL,
    lecturer_id INT NOT NULL,
    PRIMARY KEY (course_id, lecturer_id),
    CONSTRAINT fk_cl_course   FOREIGN KEY (course_id)   REFERENCES courses(id)   ON DELETE CASCADE,
    CONSTRAINT fk_cl_lecturer FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
