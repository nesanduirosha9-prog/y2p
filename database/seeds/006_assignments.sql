-- 006_assignments.sql — a handful of demo weekly-topic rows (EER
-- `Assignment`) for a couple of sessions, showing the 1:N relationship to
-- timetable_sessions (one row per week of term, not one per session).

INSERT IGNORE INTO assignments (room_code, day_of_week, start_hour, week_num, title) VALUES
    ('LAB-A201', 'mon', 8, 1, 'Setting up the development environment'),
    ('LAB-A201', 'mon', 8, 2, 'Variables, types, and basic I/O'),
    ('LAB-A201', 'mon', 8, 3, 'Control flow and loops'),
    ('LT-301',   'tue', 10, 1, 'Propositional logic'),
    ('LT-301',   'tue', 10, 2, 'Predicate logic and proofs');
