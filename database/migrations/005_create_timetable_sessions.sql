-- 005_create_timetable_sessions.sql
-- One row per block on the weekly grid. Primary key is the composite
-- (room_code, day_of_week, start_hour) instead of a surrogate id: a room
-- physically cannot host two sessions at the same day and hour, so making
-- that combination the key makes double-booking impossible at the database
-- level, which the old auto-increment schema never enforced. This assumes
-- the table holds one *current* live timetable (matches how it's always
-- been seeded: wipe and rebuild) rather than multiple terms of history side
-- by side — if that ever changes, the key will need a term/year column.
--
-- `location` (free text) is replaced by `room_code` (FK to the new `rooms`
-- table). `managed_by_code` is new — the EER's `Manages` relationship
-- (which Timetable Officer scheduled this session), nullable since existing
-- data has no such record.

CREATE TABLE timetable_sessions (
    room_code       VARCHAR(20) NOT NULL,
    day_of_week     ENUM('mon','tue','wed','thu','fri') NOT NULL,
    start_hour      TINYINT NOT NULL,           -- 24h clock, e.g. 8, 14
    course_code     VARCHAR(20) NOT NULL,
    department      ENUM('cs','is') NOT NULL,   -- denormalised from courses for fast filtering
    semester        TINYINT NOT NULL,           -- 1..2
    year_of_study   TINYINT NOT NULL,           -- 1..4
    duration_hours  TINYINT NOT NULL DEFAULT 1,
    session_type    ENUM('lecture','tutorial','lab','practical') NOT NULL DEFAULT 'lecture',
    managed_by_code VARCHAR(12) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (room_code, day_of_week, start_hour),
    CONSTRAINT fk_sessions_room    FOREIGN KEY (room_code) REFERENCES rooms(code)     ON DELETE CASCADE,
    CONSTRAINT fk_sessions_course  FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE CASCADE,
    CONSTRAINT fk_sessions_manager FOREIGN KEY (managed_by_code) REFERENCES staff(code) ON DELETE SET NULL,
    INDEX idx_sessions_filter (department, semester, year_of_study)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
