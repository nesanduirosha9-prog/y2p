-- 102_24002275_lecturers.sql — sample teaching staff (Timetable Officer).
-- Re-runnable: `code` is unique so rows are upserted.

INSERT INTO lecturers (code, name, department, email) VALUES
    ('DSC', 'Dr. Sarah Chen',      'Computer Science',    's.chen@university.edu.gh'),
    ('PJO', 'Prof. James Osei',    'Mathematics',         'j.osei@university.edu.gh'),
    ('DAD', 'Dr. Amara Diallo',    'Computer Science',    'a.diallo@university.edu.gh'),
    ('PRM', 'Prof. Richard Mensah','Computer Science',    'r.mensah@university.edu.gh'),
    ('DLW', 'Dr. Liu Wei',         'Information Systems',  'l.wei@university.edu.gh'),
    ('DEP', 'Dr. Elena Petrov',    'Computer Science',    'e.petrov@university.edu.gh'),
    ('PKA', 'Prof. Kweku Asante',  'Software Engineering', 'k.asante@university.edu.gh'),
    ('DFA', 'Dr. Fatima Ahmed',    'Computer Science',    'f.ahmed@university.edu.gh'),
    ('PDN', 'Prof. David Nkrumah', 'Computer Science',    'd.nkrumah@university.edu.gh'),
    ('DKA', 'Dr. Kofi Anning',     'Information Systems',  'k.anning@university.edu.gh'),
    ('DLO', 'Dr. Linda Osei',      'Information Systems',  'l.osei@university.edu.gh')
ON DUPLICATE KEY UPDATE
    name       = VALUES(name),
    department = VALUES(department),
    email      = VALUES(email);
