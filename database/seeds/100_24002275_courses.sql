-- 100_24002275_courses.sql — sample course catalogue (Timetable Officer).
-- Re-runnable: `code` is unique so rows are upserted. All sample data is
-- semester 1. Course -> lecturer / instructor links are seeded separately in
-- 104 / 105.

INSERT INTO courses (code, title, credits, department, year_of_study, semester) VALUES
    ('CS1101', 'Introduction to Programming',  3, 'cs', 1, 1),
    ('CS1102', 'Discrete Mathematics',         3, 'cs', 1, 1),
    ('CS1103', 'Digital Logic Design',         3, 'cs', 1, 1),
    ('CS2201', 'Data Structures & Algorithms', 4, 'cs', 2, 1),
    ('CS2202', 'Database Systems',             3, 'cs', 2, 1),
    ('CS2203', 'Operating Systems',            3, 'cs', 2, 1),
    ('CS3301', 'Software Engineering',         4, 'cs', 3, 1),
    ('CS3302', 'Computer Networks',            3, 'cs', 3, 1),
    ('CS3303', 'AI & Machine Learning',        3, 'cs', 3, 1),
    ('CS4401', 'Final Year Project',           6, 'cs', 4, 1),
    ('IS1101', 'Intro to Information Systems', 3, 'is', 1, 1),
    ('IS1103', 'Spreadsheet Applications',     3, 'is', 1, 1),
    ('IS2201', 'Systems Analysis & Design',    3, 'is', 2, 1),
    ('IS3301', 'Enterprise Systems',           3, 'is', 3, 1),
    ('IS4401', 'IS Capstone Project',          6, 'is', 4, 1)
ON DUPLICATE KEY UPDATE
    title         = VALUES(title),
    credits       = VALUES(credits),
    department    = VALUES(department),
    year_of_study = VALUES(year_of_study),
    semester      = VALUES(semester);
