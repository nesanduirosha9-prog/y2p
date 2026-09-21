-- 004_course_staff.sql — which staff are assigned to each course, and in
-- what role. Replaces the old course_lecturers + course_instructors seeds;
-- `assignment_role` is what used to be implied by which table a link lived
-- in. Re-runnable: INSERT IGNORE skips links that already exist.

INSERT IGNORE INTO course_staff (course_code, staff_code, assignment_role)
SELECT v.course, v.staff, v.role FROM (
              SELECT 'CS1101' AS course, 'DSC' AS staff, 'lecturer'   AS role
    UNION ALL SELECT 'CS1102',           'PJO',          'lecturer'
    UNION ALL SELECT 'CS1103',           'DAD',          'lecturer'
    UNION ALL SELECT 'CS2201',           'PRM',          'lecturer'
    UNION ALL SELECT 'CS2202',           'DLW',          'lecturer'
    UNION ALL SELECT 'CS2203',           'DEP',          'lecturer'
    UNION ALL SELECT 'CS3301',           'PKA',          'lecturer'
    UNION ALL SELECT 'CS3301',           'DSC',          'lecturer'
    UNION ALL SELECT 'CS3302',           'DFA',          'lecturer'
    UNION ALL SELECT 'CS3303',           'PDN',          'lecturer'
    UNION ALL SELECT 'CS3303',           'DLW',          'lecturer'
    UNION ALL SELECT 'CS4401',           'DSC',          'lecturer'
    UNION ALL SELECT 'IS1101',           'DKA',          'lecturer'
    UNION ALL SELECT 'IS1103',           'DLO',          'lecturer'
    UNION ALL SELECT 'IS2201',           'DKA',          'lecturer'
    UNION ALL SELECT 'IS3301',           'DLW',          'lecturer'
    UNION ALL SELECT 'IS4401',           'DKA',          'lecturer'

    UNION ALL SELECT 'CS1101',           'MKA',          'instructor'
    UNION ALL SELECT 'CS1102',           'MEM',          'instructor'
    UNION ALL SELECT 'CS1103',           'MKA',          'instructor'
    UNION ALL SELECT 'CS2201',           'MAB',          'instructor'
    UNION ALL SELECT 'CS2202',           'MYD',          'instructor'
    UNION ALL SELECT 'CS2203',           'MKO',          'instructor'
    UNION ALL SELECT 'CS3301',           'MNA',          'instructor'
    UNION ALL SELECT 'CS3302',           'MAT',          'instructor'
    UNION ALL SELECT 'CS3303',           'MEQ',          'instructor'
    UNION ALL SELECT 'CS4401',           'MAB',          'instructor'
    UNION ALL SELECT 'CS4401',           'MKO',          'instructor'
    UNION ALL SELECT 'IS1101',           'MAD',          'instructor'
    UNION ALL SELECT 'IS1103',           'MYB',          'instructor'
    UNION ALL SELECT 'IS2201',           'MYB',          'instructor'
    UNION ALL SELECT 'IS3301',           'MAD',          'instructor'
    UNION ALL SELECT 'IS4401',           'MYB',          'instructor'
) AS v
JOIN courses c ON c.code = v.course
JOIN staff   s ON s.code = v.staff;
