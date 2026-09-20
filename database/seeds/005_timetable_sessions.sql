-- 005_timetable_sessions.sql — sample weekly sessions. Room codes match
-- 002_rooms.sql (derived from the original free-text locations). All
-- sessions are scheduled by the seeded Timetable Officer (TMO). Re-runnable:
-- clears the table first, then rebuilds — this is the only feature that
-- writes to timetable_sessions, so the blanket DELETE is safe for dev.

DELETE FROM timetable_sessions;

INSERT INTO timetable_sessions
    (room_code, day_of_week, start_hour, course_code, department, semester, year_of_study, duration_hours, session_type, managed_by_code)
SELECT v.room_code, v.day_of_week, v.start_hour, c.code, v.department, v.semester, v.year_of_study, v.duration_hours, v.session_type, 'TMO'
FROM (
              SELECT 'CS1101' AS code, 'cs' AS department, 1 AS semester, 1 AS year_of_study, 'mon' AS day_of_week,  8 AS start_hour, 1 AS duration_hours, 'LAB-A201'  AS room_code, 'lab'      AS session_type
    UNION ALL SELECT 'CS1102',        'cs',                1,             1,                  'tue',               10,              2,                  'LT-301',                  'lecture'
    UNION ALL SELECT 'CS1103',        'cs',                1,             1,                  'wed',               14,              1,                  'ROOM-B105',               'tutorial'
    UNION ALL SELECT 'CS1101',        'cs',                1,             1,                  'thu',                9,              2,                  'LAB-A201',                'lab'
    UNION ALL SELECT 'CS2201',        'cs',                1,             2,                  'mon',                9,              2,                  'LT-401',                  'lecture'
    UNION ALL SELECT 'CS2202',        'cs',                1,             2,                  'tue',                8,              1,                  'LAB-C301',                'lab'
    UNION ALL SELECT 'CS2201',        'cs',                1,             2,                  'wed',               13,              1,                  'ROOM-D201',               'tutorial'
    UNION ALL SELECT 'CS2203',        'cs',                1,             2,                  'fri',               15,              1,                  'LT-302',                  'lecture'
    UNION ALL SELECT 'IS1101',        'is',                1,             1,                  'mon',                8,              1,                  'LT-201',                  'lecture'
    UNION ALL SELECT 'IS1103',        'is',                1,             1,                  'fri',               14,              1,                  'LAB-B101',                'lab'
) AS v
JOIN courses c ON c.code = v.code;
