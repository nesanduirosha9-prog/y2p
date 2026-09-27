-- 001_staff.sql
-- Seeds the merged `staff` table: the two demo login accounts (timetable
-- officer + instructor) plus the former `lecturers` (now academic_rank
-- 'senior') and `instructors` (now academic_rank 'junior') rosters. TAs
-- never had emails/passwords before since they weren't logins — given a
-- @ucsc.cmb.ac.lk address and a shared placeholder password so every staff
-- row can log in like the EER intends.
--
-- MKA is given position='coordinator' and DSC position='in_charge' so the
-- reschedule_requests/support_requests seeds have valid coordinator/senior
-- staff to reference. Every account (including TMF, whose original
-- pre-rewrite password hash we have no plaintext for) shares the same
-- placeholder password "Password123!" (bcrypt below); re-runnable via
-- ON DUPLICATE KEY UPDATE.

INSERT INTO staff (code, email, password, name, role, academic_rank, position, department, designation, office, extension, bio, availability_status) VALUES
    ('TMO', 'tmo@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'T. M. Officer', 'timetable_officer', NULL, NULL, NULL, 'Timetable Officer', NULL, NULL, NULL, 'available'),
    ('TMF', 'tmf@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Ms. Thilini Fernando', 'academic_staff', 'junior', NULL, 'Computer Science', 'Instructor', 'Room 204, Science Block B', '4512', 'Instructor supporting CS3401 and related lab sessions.', 'available'),

    ('DSC', 'dsc@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Dr. S. Chandrasekara', 'academic_staff', 'senior', 'in_charge', 'Computer Science',     'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('PJO', 'pjo@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Prof. J. Opanayake',   'academic_staff', 'senior', NULL,        'Information Systems',          'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('DAD', 'dad@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Dr. A. Dissanayake',   'academic_staff', 'senior', NULL,        'Computer Science',     'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('PRM', 'prm@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Prof. R. Munasinghe',  'academic_staff', 'senior', NULL,        'Computer Science',     'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('DLW', 'dlw@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Dr. L. Weerasinghe',   'academic_staff', 'senior', NULL,        'Information Systems',  'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('DEP', 'dep@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Dr. E. Pathirana',     'academic_staff', 'senior', NULL,        'Computer Science',     'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('PKA', 'pka@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Prof. K. Abeysekara',  'academic_staff', 'senior', NULL,        'Software Engineering', 'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('DFA', 'dfa@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Dr. F. Ariyaratne',    'academic_staff', 'senior', NULL,        'Computer Science',     'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('PDN', 'pdn@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Prof. D. Nawarathne',  'academic_staff', 'senior', NULL,        'Computer Science',     'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('DKA', 'dka@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Dr. K. Attanayake',    'academic_staff', 'senior', NULL,        'Information Systems',  'Senior Lecturer', NULL, NULL, NULL, 'available'),
    ('DLO', 'dlo@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Dr. L. Obeyesekere',   'academic_staff', 'senior', NULL,        'Information Systems',  'Senior Lecturer', NULL, NULL, NULL, 'available'),

    ('MKA', 'mka@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Mr. K. Abeywickrama', 'academic_staff', 'junior', 'coordinator', NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('AMD', 'amd@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Mr. Amod Pathirana',  'academic_staff', 'junior', 'coordinator', NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('TSS' ,'tss@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Mr. Tharindu Sampath',   'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MEM', 'mem@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Ms. E. Mendis',       'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MAB', 'mab@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Mr. A. Balasuriya',   'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MYD', 'myd@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Ms. Y. Dharmasena',   'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MKO', 'mko@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Mr. K. Obeyesekere',  'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MNA', 'mna@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Ms. N. Amarasinghe',  'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MAT', 'mat@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Mr. A. Tissera',      'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MEQ', 'meq@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Ms. E. Qadir',        'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MAD', 'mad@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Mr. A. Dias',         'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available'),
    ('MYB', 'myb@ucsc.cmb.ac.lk', '$2y$12$iJjxOJdiq1zMLR.irAdCBecoTHl5VxsGLko8Ye8wiPM5OFGoQaD6S', 'Ms. Y. Bandara',      'academic_staff', 'junior', NULL,          NULL, 'Instructor', NULL, NULL, NULL, 'available')
ON DUPLICATE KEY UPDATE
    name          = VALUES(name),
    role          = VALUES(role),
    academic_rank = VALUES(academic_rank),
    position      = VALUES(position),
    department    = VALUES(department),
    designation   = VALUES(designation);
