-- 015_support_requests.sql — demo rows matching the shapes already designed
-- in app/views/instructor/requests.php, mapped onto real seeded courses.

DELETE FROM support_requests;

INSERT INTO support_requests (id, course_code, requested_by_code, support_type, preferred_staff_code, notes, status) VALUES
    (UUID(), 'CS2202', 'TMF', 'lab_assistant',      NULL,  'Need someone to assist with equipment.', 'pending'),
    (UUID(), 'CS2201', 'TMF', 'technical_support',  'MKA', 'Server setup help',                      'approved');
