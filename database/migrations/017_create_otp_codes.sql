-- 017_create_otp_codes.sql
-- Backs AuthController::sendOtp()/verifyOtp() for the password-reset flow.
-- Only `password_reset` is a valid `purpose` — the other OTP flow in this
-- codebase (role handover, in_charge/AccountsController) deliberately stays
-- on $_SESSION['handover'] per product decision, so it never writes here.
--
-- Keyed by a UUID like notifications/messages/etc — an email can legitimately
-- request several codes over time, so there's no natural unique key here.

CREATE TABLE otp_codes (
    id                 CHAR(36)     NOT NULL PRIMARY KEY,
    email              VARCHAR(255) NOT NULL,
    purpose            ENUM('password_reset') NOT NULL,
    otp_hash           VARCHAR(255) NOT NULL,
    expires_at         DATETIME NOT NULL,
    attempts_remaining TINYINT UNSIGNED NOT NULL DEFAULT 5,
    consumed_at        DATETIME NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_codes_lookup (email, purpose, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
