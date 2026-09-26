-- 009_create_leave_requests.sql
-- EER `Leave Request`. Leave is approved on paper, outside the system; this
-- is the digital record of it, kept for history and for planning workload
-- around who is away. So there is no approval state: a request is recorded
-- as soon as it is submitted.
--
-- Split in two because the Leave page picks individual dates (not a range)
-- and assigns a cover person to EACH date:
--
--   leave_requests  one row per request: who, what type, why and the hours.
--                   start_date/end_date are the first and last picked date,
--                   kept here so "upcoming vs history" and the edit / cancel
--                   rule never need a join.
--   leave_days      one row per picked date, with that day's cover person.
--                   One person covering several days is just the same
--                   cover_code repeated across rows.
--
-- The time range (time_from/time_to) applies to every day of the request —
-- the request panel sets one "Specific Time" for all picked dates. Both NULL
-- means full days.
--
-- Cancelling a request DELETEs it (leave_days follow by ON DELETE CASCADE).
-- `leave_type` matches the options in app/views/instructor/leave.php's
-- request panel.
--
-- Keyed by a UUID: two requests can be identical in every attribute if one is
-- resubmitted, so nothing here is a safe natural key.

CREATE TABLE leave_requests (
    id              CHAR(36)    NOT NULL PRIMARY KEY,
    requester_code  VARCHAR(12) NOT NULL,
    leave_type      ENUM('sick','other') NOT NULL,
    start_date      DATE        NOT NULL,
    end_date        DATE        NOT NULL,
    time_from       TIME        NULL,
    time_to         TIME        NULL,
    reason          TEXT        NULL,
    created_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    -- Target of leave_days' composite FK below.
    UNIQUE KEY uq_leave_id_requester (id, requester_code),
    CONSTRAINT fk_leave_requester FOREIGN KEY (requester_code) REFERENCES staff(code) ON DELETE CASCADE,
    CONSTRAINT chk_leave_range CHECK (end_date >= start_date),
    CONSTRAINT chk_leave_hours CHECK (
        (time_from IS NULL AND time_to IS NULL) OR
        (time_from IS NOT NULL AND time_to IS NOT NULL AND time_from < time_to)
    ),
    INDEX idx_leave_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Two clashes are stopped by the database itself, not just by the app:
--   uq_leave_days_requester  a member cannot have two leaves on the same day
--   uq_leave_days_cover      a member cannot cover two leaves on the same day
--
-- requester_code is copied from the parent so uq_leave_days_requester can be
-- a plain index. The composite FK (leave_id, requester_code) guarantees the
-- copy always matches the parent row.
CREATE TABLE leave_days (
    leave_id        CHAR(36)    NOT NULL,
    requester_code  VARCHAR(12) NOT NULL,
    leave_date      DATE        NOT NULL,
    cover_code      VARCHAR(12) NOT NULL,
    PRIMARY KEY (leave_id, leave_date),
    UNIQUE KEY uq_leave_days_requester (requester_code, leave_date),
    UNIQUE KEY uq_leave_days_cover     (cover_code, leave_date),
    CONSTRAINT fk_leave_days_request FOREIGN KEY (leave_id, requester_code)
        REFERENCES leave_requests(id, requester_code) ON DELETE CASCADE,
    -- No ON DELETE: removing a staff member who is covering someone's leave
    -- must fail rather than leave that day silently uncovered.
    CONSTRAINT fk_leave_days_cover FOREIGN KEY (cover_code) REFERENCES staff(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
