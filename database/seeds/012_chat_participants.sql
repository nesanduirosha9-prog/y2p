-- 012_chat_participants.sql — who's in the demo group chat seeded in 011.
-- This table didn't exist in the original EER at all; without it there's no
-- way to know chat room membership.

INSERT IGNORE INTO chat_participants (chat_room_id, staff_code) VALUES
    (@chat_cs2202_lab_group, 'TMF'),
    (@chat_cs2202_lab_group, 'DSC'),
    (@chat_cs2202_lab_group, 'MKA');
