-- 012_create_chat_participants.sql
-- New table — the original EER never modeled chat membership at all, even
-- though Chat_Type implies group chats need one. Without this there is no
-- way to know who is in a given chat room.

CREATE TABLE chat_participants (
    chat_room_id CHAR(36)    NOT NULL,
    staff_code   VARCHAR(12) NOT NULL,
    PRIMARY KEY (chat_room_id, staff_code),
    CONSTRAINT fk_participant_room  FOREIGN KEY (chat_room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_participant_staff FOREIGN KEY (staff_code)   REFERENCES staff(code)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
