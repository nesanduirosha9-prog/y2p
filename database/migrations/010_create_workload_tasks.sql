-- 010_create_workload_tasks.sql
-- EER `Workload Task`. The original diagram had TWO separate M:N
-- relationships reaching it (`Delegates` from Academic Staff, `Assigned to`
-- from Junior Staff) — redundant, since Junior Staff is already a subset of
-- Academic Staff, and the real stub data in app/views/instructor/workload.php
-- only ever has exactly one assignee per task. Collapsed to a single
-- `assigned_staff_code` FK.
--
-- Keyed by a UUID: task title/type/hours can repeat across different weeks
-- or courses with no other distinguishing attribute.

CREATE TABLE workload_tasks (
    id                 CHAR(36) NOT NULL PRIMARY KEY,
    title              VARCHAR(150) NOT NULL,
    task_type          VARCHAR(80) NULL,          -- e.g. "Lab Supervisor", "Lab Supervision"
    course_code        VARCHAR(20) NULL,
    assigned_staff_code VARCHAR(12) NOT NULL,
    hours              DECIMAL(4,1) NOT NULL,
    due_date           DATE NULL,
    status             ENUM('assigned','completed') NOT NULL DEFAULT 'assigned',
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_workload_course FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE SET NULL,
    CONSTRAINT fk_workload_staff  FOREIGN KEY (assigned_staff_code) REFERENCES staff(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
