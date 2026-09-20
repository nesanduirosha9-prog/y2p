-- 015_create_support_requests.sql
-- New entity — matches app/views/instructor/requests.php's "Supportive
-- Staff Requests" feature (requesting a lab assistant / technical support
-- person for a course), which the original EER never modeled at all; it's
-- unrelated to Reschedule Request (014), confirmed as a separate concept
-- with the user. `support_type` values are pulled directly from that view's
-- $supportTypes array so the schema matches the UI exactly.
--
-- Keyed by UUID: two requests can share every attribute (same course, same
-- support type, same notes) if submitted twice.

CREATE TABLE support_requests (
    id                  CHAR(36) NOT NULL PRIMARY KEY,
    course_code         VARCHAR(20) NOT NULL,
    requested_by_code   VARCHAR(12) NOT NULL,
    support_type        ENUM('lab_assistant','technical_support','equipment_setup','it_support','administrative_support') NOT NULL,
    preferred_staff_code VARCHAR(12) NULL,
    notes               TEXT NULL,
    status              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_support_course    FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE CASCADE,
    CONSTRAINT fk_support_requester FOREIGN KEY (requested_by_code) REFERENCES staff(code) ON DELETE CASCADE,
    CONSTRAINT fk_support_preferred FOREIGN KEY (preferred_staff_code) REFERENCES staff(code) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
