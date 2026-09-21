-- 013_create_messages.sql
-- EER `Message` / `Sends`. The original EER restricted `Sends` to Academic
-- Staff only, excluding Timetable Officer for no clear product reason —
-- widened here so any staff member can send a message in a room they
-- participate in (enforced at the app layer via chat_participants, not the
-- schema). Keyed by UUID: message content + timestamp can coincidentally
-- repeat and still be two distinct messages.

CREATE TABLE messages (
    id           CHAR(36) NOT NULL PRIMARY KEY,
    chat_room_id CHAR(36)    NOT NULL,
    sender_code  VARCHAR(12) NOT NULL,
    content      TEXT NOT NULL,
    sent_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_room   FOREIGN KEY (chat_room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_sender FOREIGN KEY (sender_code)  REFERENCES staff(code)     ON DELETE CASCADE,
    INDEX idx_messages_room_time (chat_room_id, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
