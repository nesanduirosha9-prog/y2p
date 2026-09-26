<?php

namespace app\services;

use app\models\OtpCodeModel;

// OtpService: issue and check the 6-digit email codes stored in otp_codes.
// Shared by the sign-in pages (AuthController: signup, forgot password) and
// Settings → Password (SettingsController), so all of them get the same
// rate limits and expiry. The role-handover flow keeps its own session-based
// code — see in_charge/AccountsController.
//
// DEMO_AUTH (config.php) simulates both steps: nothing is emailed and any
// 6-digit code verifies.
class OtpService
{
    /**
     * Email a fresh code for $purpose ('signup' | 'password_reset').
     * Returns null once sent, or [message, httpStatus] when it was refused —
     * 60s cooldown, 5 per hour, or the email could not be delivered (a code
     * that was never delivered is never stored).
     */
    public static function send(string $email, string $purpose): ?array
    {
        if (self::demo()) {
            return null;
        }

        $otpModel = new OtpCodeModel();
        if ($otpModel->countRequestsSince($email, $purpose, 60) > 0) {
            return ['Please wait a minute before requesting another code.', 429];
        }
        if ($otpModel->countRequestsSince($email, $purpose, 3600) >= 5) {
            return ['Too many requests. Please try again later.', 429];
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if (!EmailService::sendOtpEmail($email, $otp, $purpose)) {
            return ['Could not send the code. Please try again.', 500];
        }
        $otpModel->create($email, $purpose, $otp);
        return null;
    }

    /**
     * Check $otp against the latest active code for $email/$purpose and burn
     * it on a match. Returns null when it verifies, otherwise the message to
     * show. A wrong guess costs one of the code's 5 attempts.
     */
    public static function verify(string $email, string $purpose, string $otp): ?string
    {
        if (!preg_match('/^\d{6}$/', $otp)) {
            return 'Enter the 6-digit code';
        }
        if (self::demo()) {
            return null;
        }

        $otpModel = new OtpCodeModel();
        $row = $otpModel->findLatestActive($email, $purpose);
        if (!$row) {
            return 'Invalid or expired code';
        }
        if (!password_verify($otp, $row['otp_hash'])) {
            $otpModel->decrementAttempts($row['id']);
            return 'Incorrect code';
        }

        $otpModel->markConsumed($row['id']);
        return null;
    }

    private static function demo(): bool
    {
        return defined('DEMO_AUTH') && DEMO_AUTH;
    }
}
