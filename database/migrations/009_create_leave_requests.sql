-- 009_create_leave_requests.sql
-- EER `Leave Request`, with `Submits` and `Covers` implemented as plain FK
-- columns (requester_code, cover_code) instead of relationship diamonds —
-- both were 1:N from Staff, so a diamond added nothing but the dangling,
-- unbound edges the original diagram had. `leave_type` reflects the actual
-- options already designed in app/views/instructor/leave.php's request
-- modal (Casual/Sick/Annual/Conference), which the EER never specified.
--
-- Keyed by a UUID: two leave requests can legitimately be identical in
-- every attribute (requester, dates, reason) if a request is resubmitted,
-- so nothing here is a safe natural key.

CREATE TABLE leave_requests (
    id              CHAR(36) NOT NULL PRIMARY KEY,
    requester_code  VARCHAR(12) NOT NULL,
    cover_code      VARCHAR(12) NULL,
    leave_type      ENUM('casual','sick','annual','conference') NOT NULL,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    reason          TEXT NOT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_leave_requester FOREIGN KEY (requester_code) REFERENCES staff(code) ON DELETE CASCADE,
    CONSTRAINT fk_leave_cover     FOREIGN KEY (cover_code)     REFERENCES staff(code) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
