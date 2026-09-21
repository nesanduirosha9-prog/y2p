-- 010_workload_tasks.sql — demo workload tasks assigned to seeded staff.
-- Course codes are drawn from the real seeded catalogue (003_courses.sql),
-- not the fictional course codes used in the instructor/workload.php stub.

DELETE FROM workload_tasks;

INSERT INTO workload_tasks (id, title, task_type, course_code, assigned_staff_code, hours, due_date, status) VALUES
    (UUID(), 'Database Systems Lab Supervision', 'Lab Supervisor',   'CS2202', 'MKA', 4.0, '2025-07-02', 'completed'),
    (UUID(), 'Data Structures Tutorial Marking',  'Tutorial Grading', 'CS2201', 'TMF', 3.0, '2025-07-10', 'assigned');
