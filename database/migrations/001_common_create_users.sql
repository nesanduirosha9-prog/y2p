-- 001_common_create_users.sql
-- COMMON schema — shared by the auth feature and every role dashboard.
-- Owned collectively: coordinate with the whole team before changing this,
-- and never edit it once merged — add a new NNN_common_*.sql instead.
--
-- Extracted verbatim from the old app/core/Database.php::setupTables().

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
