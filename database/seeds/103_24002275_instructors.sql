-- 103_24002275_instructors.sql — sample teaching assistants (Timetable Officer).
-- Re-runnable: `code` is unique so rows are upserted.

INSERT INTO instructors (code, name) VALUES
    ('MKA', 'Mr. Kwame Addo'),
    ('MEM', 'Ms. Efua Mensah'),
    ('MAB', 'Mr. Ato Baidoo'),
    ('MYD', 'Ms. Yaa Darko'),
    ('MKO', 'Mr. Kojo Amoah'),
    ('MNA', 'Ms. Nana Ama'),
    ('MAT', 'Mr. Atta Tetteh'),
    ('MEQ', 'Ms. Esi Quaye'),
    ('MAD', 'Mr. Adom Boateng'),
    ('MYB', 'Ms. Yaw Bediako')
ON DUPLICATE KEY UPDATE
    name = VALUES(name);
