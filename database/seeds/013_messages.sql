-- 013_messages.sql — a couple of demo messages in the group chat seeded in
-- 011. Any staff can send (not just academic staff) — see the note in
-- 013_create_messages.sql.

DELETE FROM messages;

INSERT INTO messages (id, chat_room_id, sender_code, content, sent_at) VALUES
    (UUID(), @chat_cs2202_lab_group, 'DSC', 'Reminder: lab practical moved to LAB-C301 this week.', NOW() - INTERVAL 2 HOUR),
    (UUID(), @chat_cs2202_lab_group, 'MKA', 'Got it, I will let the students know.',                 NOW() - INTERVAL 1 HOUR),
    (UUID(), @chat_cs2202_lab_group, 'TMF', 'Thanks for the heads up!',                               NOW() - INTERVAL 50 MINUTE);
