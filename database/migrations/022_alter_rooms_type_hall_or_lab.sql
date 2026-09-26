-- 022_alter_rooms_type_hall_or_lab.sql
-- A room is a lecture hall or a lab — nothing else (021 moved the old
-- 'tutorial_room' / 'other' rows over first).
ALTER TABLE rooms
    MODIFY COLUMN type ENUM('lab', 'lecture_hall') NOT NULL DEFAULT 'lecture_hall';
