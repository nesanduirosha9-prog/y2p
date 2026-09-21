-- 014_create_reschedule_requests.sql
-- EER `Reschedule Request`, exactly as the diagram designed it: a Senior
-- Lecturer requests moving a Timetable Session, a Coordinator confirms it.
-- This is a real but not-yet-built feature — confirmed with the user as a
-- distinct entity from "Supportive Staff Request"
-- (015_create_support_requests.sql), which is a different, already-designed
-- feature the EER never modeled.
--
-- `requested_by_code` must belong to a staff row with academic_rank='senior'
-- (EER: `Requests`), `confirmed_by_code` must belong to a staff row with
-- position='coordinator' (EER: `Confirms`) — both enforced at the app layer,
-- since a cross-table CHECK against another table's column isn't possible in
-- MySQL. Keyed by UUID: a session could plausibly have more than one
-- reschedule attempt over time with identical requested slot/weeks.

CREATE TABLE reschedule_requests (
    id                   CHAR(36) NOT NULL PRIMARY KEY,
    room_code            VARCHAR(20) NOT NULL,
    day_of_week          ENUM('mon','tue','wed','thu','fri') NOT NULL,
    start_hour           TINYINT NOT NULL,
    requested_by_code    VARCHAR(12) NOT NULL,
    confirmed_by_code    VARCHAR(12) NULL,
    requested_day        ENUM('mon','tue','wed','thu','fri') NOT NULL,
    requested_start_hour TINYINT NOT NULL,
    target_weeks         VARCHAR(100) NULL,  -- e.g. "3,5,7" — which weeks of term this applies to
    status               ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reschedule_session
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE,
    CONSTRAINT fk_reschedule_requester FOREIGN KEY (requested_by_code) REFERENCES staff(code) ON DELETE CASCADE,
    CONSTRAINT fk_reschedule_confirmer FOREIGN KEY (confirmed_by_code) REFERENCES staff(code) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
