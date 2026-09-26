-- 020_alter_otp_codes_signup_purpose.sql
-- Signup now verifies the email with a real OTP too
-- (AuthController::sendSignupOtp()/verifySignupOtp()), so its codes live in
-- otp_codes alongside password-reset ones.
ALTER TABLE otp_codes
    MODIFY purpose ENUM('password_reset', 'signup') NOT NULL;
