-- 009_leave_requests.sql — demo leave across junior and senior staff, some
-- upcoming and some already taken (history), with full-day and part-day
-- examples.
--
-- Dates are relative to CURDATE() so upcoming vs history always splits
-- sensibly whenever the database is reseeded. Covers are the same rank as the
-- requester, and no one covers or takes two leaves on the same day — the
-- unique keys on leave_days would reject the seed otherwise.

DELETE FROM leave_requests;

SET @l1 = UUID(), @l2 = UUID(), @l3 = UUID(), @l4 = UUID(), @l5 = UUID(),
    @l6 = UUID(), @l7 = UUID(), @l8 = UUID(), @l9 = UUID();

INSERT INTO leave_requests
    (id, requester_code, leave_type, start_date, end_date, time_from, time_to, reason, created_at) VALUES
    (@l1, 'TMF', 'sick',  CURDATE() + INTERVAL 3 DAY,   CURDATE() + INTERVAL 3 DAY,   '09:00', '12:00', 'Medical appointment',        NOW() - INTERVAL 1 DAY),
    (@l2, 'TMF', 'other', CURDATE() + INTERVAL 12 DAY,  CURDATE() + INTERVAL 14 DAY,  NULL,    NULL,    'Curriculum research',        NOW() - INTERVAL 2 DAY),
    (@l3, 'MEM', 'other', CURDATE() + INTERVAL 7 DAY,   CURDATE() + INTERVAL 8 DAY,   NULL,    NULL,    'Attending ICTer conference', NOW() - INTERVAL 6 DAY),
    (@l4, 'MAB', 'sick',  CURDATE() - INTERVAL 35 DAY,  CURDATE() - INTERVAL 33 DAY,  NULL,    NULL,    'Fever recovery',             NOW() - INTERVAL 37 DAY),
    (@l5, 'PJO', 'other', CURDATE() - INTERVAL 20 DAY,  CURDATE() - INTERVAL 19 DAY,  NULL,    NULL,    'External examiner duty',     NOW() - INTERVAL 27 DAY),
    (@l6, 'MYD', 'other', CURDATE() + INTERVAL 5 DAY,   CURDATE() + INTERVAL 5 DAY,   NULL,    NULL,    'Personal matter',            NOW() - INTERVAL 4 DAY),
    (@l7, 'MKA', 'other', CURDATE() + INTERVAL 10 DAY,  CURDATE() + INTERVAL 10 DAY,  '13:00', '16:00', 'Graduation ceremony',        NOW() - INTERVAL 5 HOUR),
    (@l8, 'DAD', 'sick',  CURDATE() - INTERVAL 70 DAY,  CURDATE() - INTERVAL 70 DAY,  NULL,    NULL,    NULL,                         NOW() - INTERVAL 71 DAY),
    (@l9, 'MNA', 'other', CURDATE() + INTERVAL 20 DAY,  CURDATE() + INTERVAL 21 DAY,  NULL,    NULL,    'Family wedding',             NOW() - INTERVAL 3 HOUR);

INSERT INTO leave_days (leave_id, requester_code, leave_date, cover_code) VALUES
    (@l1, 'TMF', CURDATE() + INTERVAL 3 DAY,  'MEM'),
    (@l2, 'TMF', CURDATE() + INTERVAL 12 DAY, 'MKO'),
    (@l2, 'TMF', CURDATE() + INTERVAL 13 DAY, 'MKO'),
    (@l2, 'TMF', CURDATE() + INTERVAL 14 DAY, 'MNA'),
    (@l3, 'MEM', CURDATE() + INTERVAL 7 DAY,  'MAB'),
    (@l3, 'MEM', CURDATE() + INTERVAL 8 DAY,  'MAB'),
    (@l4, 'MAB', CURDATE() - INTERVAL 35 DAY, 'MNA'),
    (@l4, 'MAB', CURDATE() - INTERVAL 34 DAY, 'MYD'),
    (@l4, 'MAB', CURDATE() - INTERVAL 33 DAY, 'MYD'),
    (@l5, 'PJO', CURDATE() - INTERVAL 20 DAY, 'DAD'),
    (@l5, 'PJO', CURDATE() - INTERVAL 19 DAY, 'DAD'),
    (@l6, 'MYD', CURDATE() + INTERVAL 5 DAY,  'MKO'),
    (@l7, 'MKA', CURDATE() + INTERVAL 10 DAY, 'MAT'),
    (@l8, 'DAD', CURDATE() - INTERVAL 70 DAY, 'PRM'),
    (@l9, 'MNA', CURDATE() + INTERVAL 20 DAY, 'MEQ'),
    (@l9, 'MNA', CURDATE() + INTERVAL 21 DAY, 'MAD');
