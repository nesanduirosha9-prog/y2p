-- 004_create_course_staff.sql
-- Replaces `course_lecturers` + `course_instructors`. The EER models one
-- M:N relationship between Academic Staff and Course ("Assigned To") — the
-- old schema instead split it into two separate tables keyed by a rank the
-- person was permanently assigned (lecturer vs instructor), which doesn't
-- match reality: whether someone is the lecturer-of-record or a supporting
-- instructor is a property of *this course assignment*, not of the person.
-- `assignment_role` captures that per row, independent of `staff.academic_rank`.

CREATE TABLE course_staff (
    course_code     VARCHAR(20) NOT NULL,
    staff_code      VARCHAR(12) NOT NULL,
    assignment_role ENUM('lecturer', 'instructor') NOT NULL,
    PRIMARY KEY (course_code, staff_code),
    CONSTRAINT fk_cs_course FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE CASCADE,
    CONSTRAINT fk_cs_staff  FOREIGN KEY (staff_code)  REFERENCES staff(code)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
