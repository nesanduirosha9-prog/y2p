-- 002_create_rooms.sql
-- New table for the EER's `Workspace/Room` entity, which never had a real
-- table before — `timetable_sessions.location` was free text, so there was
-- no capacity tracking and no way to reuse/reference a room. Keyed by its
-- own `code` (e.g. "LAB-A201"), which timetable_sessions will fold into its
-- own composite key (see 005_create_timetable_sessions.sql).

CREATE TABLE rooms (
    code       VARCHAR(20) NOT NULL PRIMARY KEY,
    type       ENUM('lab', 'lecture_hall', 'tutorial_room', 'other') NOT NULL DEFAULT 'other',
    capacity   INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
