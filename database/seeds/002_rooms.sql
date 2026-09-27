-- 002_rooms.sql
-- Room codes/types derived from the location strings the old
-- timetable_sessions seed used ("Lab A-201", "LT-301", "Room B-105", ...).
-- Capacities are reasonable defaults per room type, not real UCSC data.

INSERT INTO rooms (code, type, capacity) VALUES
    ('LAB-A', 'lab',           30),
    ('LT-301',   'lecture_hall', 120),
    ('LT-105','lecture_hall',  40),
    ('LT-401',   'lecture_hall', 120),
    ('LAB-B', 'lab',           30),
    ('LT-201','lecture_hall',  40),
    ('LT-302',   'lecture_hall', 120),
    ('LT-202',   'lecture_hall', 100),
    ('LAB-C', 'lab',           30)
ON DUPLICATE KEY UPDATE
    type     = VALUES(type),
    capacity = VALUES(capacity);
