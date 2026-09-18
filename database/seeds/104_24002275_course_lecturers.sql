-- 104_24002275_course_lecturers.sql — which lecturers teach each course.
-- Re-runnable: INSERT IGNORE skips links that already exist.

INSERT IGNORE INTO course_lecturers (course_id, lecturer_id)
SELECT c.id, l.id
FROM (
              SELECT 'CS1101' AS course, 'DSC' AS lecturer
    UNION ALL SELECT 'CS1102', 'PJO'
    UNION ALL SELECT 'CS1103', 'DAD'
    UNION ALL SELECT 'CS2201', 'PRM'
    UNION ALL SELECT 'CS2202', 'DLW'
    UNION ALL SELECT 'CS2203', 'DEP'
    UNION ALL SELECT 'CS3301', 'PKA'
    UNION ALL SELECT 'CS3301', 'DSC'
    UNION ALL SELECT 'CS3302', 'DFA'
    UNION ALL SELECT 'CS3303', 'PDN'
    UNION ALL SELECT 'CS3303', 'DLW'
    UNION ALL SELECT 'CS4401', 'DSC'
    UNION ALL SELECT 'IS1101', 'DKA'
    UNION ALL SELECT 'IS1103', 'DLO'
    UNION ALL SELECT 'IS2201', 'DKA'
    UNION ALL SELECT 'IS3301', 'DLW'
    UNION ALL SELECT 'IS4401', 'DKA'
) AS v
JOIN courses   c ON c.code = v.course
JOIN lecturers l ON l.code = v.lecturer;
