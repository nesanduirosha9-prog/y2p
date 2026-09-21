-- 014_reschedule_requests.sql — a demo reschedule request against a real
-- seeded session (CS1102, Tue 10:00, LT-301). requested_by must be
-- academic_rank='senior' (DSC), confirmed_by must be position='coordinator'
-- (MKA) — enforced at the app layer, matching the EER's Requests/Confirms.

DELETE FROM reschedule_requests;

INSERT INTO reschedule_requests (id, room_code, day_of_week, start_hour, requested_by_code, confirmed_by_code, requested_day, requested_start_hour, target_weeks, status) VALUES
    (UUID(), 'LT-301', 'tue', 10, 'DSC', 'MKA', 'thu', 10, '5,6', 'confirmed');
