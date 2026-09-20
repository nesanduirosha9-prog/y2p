-- 105_24002275_course_instructors.sql — which instructors (TAs) are on each course.
-- Re-runnable: INSERT IGNORE skips links that already exist.

INSERT IGNORE INTO course_instructors (course_id, instructor_id)
SELECT c.id, i.id
FROM (
              SELECT 'CS1101' AS course, 'MKA' AS instructor
    UNION ALL SELECT 'CS1102', 'MEM'
    UNION ALL SELECT 'CS1103', 'MKA'
    UNION ALL SELECT 'CS2201', 'MAB'
    UNION ALL SELECT 'CS2202', 'MYD'
    UNION ALL SELECT 'CS2203', 'MKO'
    UNION ALL SELECT 'CS3301', 'MNA'
    UNION ALL SELECT 'CS3302', 'MAT'
    UNION ALL SELECT 'CS3303', 'MEQ'
    UNION ALL SELECT 'CS4401', 'MAB'
    UNION ALL SELECT 'CS4401', 'MKO'
    UNION ALL SELECT 'IS1101', 'MAD'
    UNION ALL SELECT 'IS1103', 'MYB'
    UNION ALL SELECT 'IS2201', 'MYB'
    UNION ALL SELECT 'IS3301', 'MAD'
    UNION ALL SELECT 'IS4401', 'MYB'
) AS v
JOIN courses     c ON c.code = v.course
JOIN instructors i ON i.code = v.instructor;
