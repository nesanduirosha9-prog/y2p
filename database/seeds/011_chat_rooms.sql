-- 011_chat_rooms.sql — a demo course-scoped group chat. Id captured into a
-- session variable so 012_chat_participants.sql and 013_messages.sql (run
-- right after, same connection) can reference it directly.

DELETE FROM chat_rooms;

SET @chat_cs2202_lab_group = UUID();

INSERT INTO chat_rooms (id, name, chat_type, course_code) VALUES
    (@chat_cs2202_lab_group, 'CS2202 Lab Group', 'group', 'CS2202');
