-- 011_create_chat_rooms.sql
-- EER `Chat Room`. The original diagram had this 1:1 with a single Workload
-- Task, but the real UI (app/views/instructor/messages.php) shows chat
-- rooms scoped to a *course* ("CS3401 Lab Group"), not one task — corrected
-- to a nullable `course_code` FK instead. Keyed by UUID: room name/type can
-- repeat (e.g. multiple "Lab Group" chats across different course offerings).

CREATE TABLE chat_rooms (
    id          CHAR(36) NOT NULL PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    chat_type   ENUM('group','direct') NOT NULL,
    course_code VARCHAR(20) NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chatroom_course FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
