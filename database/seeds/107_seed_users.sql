-- 107_seed_users.sql
-- Seed standard instructor account

INSERT INTO users (email, password, role) VALUES
    ('tmf@ucsc.cmb.ac.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor')
ON DUPLICATE KEY UPDATE role = VALUES(role);
