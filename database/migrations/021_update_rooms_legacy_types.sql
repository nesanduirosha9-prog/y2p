-- 021_update_rooms_legacy_types.sql
-- The department only has two kinds of teaching space: lecture halls and labs.
-- Rooms saved as 'tutorial_room' or 'other' become lecture halls, so that
-- 022 can drop those two values from the ENUM without failing on existing
-- rows. (One statement per file — see database/migrate.php.)
UPDATE rooms SET type = 'lecture_hall' WHERE type IN ('tutorial_room', 'other');
