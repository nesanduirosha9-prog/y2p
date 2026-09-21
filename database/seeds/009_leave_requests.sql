-- 009_leave_requests.sql — a couple of demo leave requests for the seeded
-- instructor account (TMF), one covered by another staff member, one not
-- yet assigned a cover.

DELETE FROM leave_requests;

INSERT INTO leave_requests (id, requester_code, cover_code, leave_type, start_date, end_date, reason, status) VALUES
    (UUID(), 'TMF', 'DSC', 'casual', '2025-06-10', '2025-06-12', 'Family event', 'approved'),
    (UUID(), 'TMF', NULL,  'sick',   '2025-08-01', '2025-08-02', 'Not feeling well', 'pending');
