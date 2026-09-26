<?php

namespace app\models;

use app\core\Database;
use app\core\Uuid;
use PDO;

// OtpCodeModel: one-time codes for the password-reset and signup flows
// (AuthController::sendOtp()/sendSignupOtp() and verifyOtpFor(); `purpose`
// tells them apart). The other OTP flow in this codebase — role
// handover, in_charge/AccountsController — deliberately stays on
// $_SESSION['handover'] instead of this table; see 017_create_otp_codes.sql.
//
// Expiry/rate-limit comparisons run against the DB clock (NOW(),
// TIMESTAMPDIFF) rather than PHP's, matching NotificationModel's convention.
class OtpCodeModel
{
    /** Store a freshly-generated code, hashed, expiring in 5 minutes. */
    public function create(string $email, string $purpose, string $otp): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO otp_codes (id, email, purpose, otp_hash, expires_at, attempts_remaining)
             VALUES (:id, :email, :purpose, :hash, NOW() + INTERVAL 5 MINUTE, 5)"
        );
        return $stmt->execute([
            'id' => Uuid::v4(),
            'email' => $email,
            'purpose' => $purpose,
            'hash' => password_hash($otp, PASSWORD_DEFAULT),
        ]);
    }

    /** How many codes were requested for this email/purpose in the last $seconds — backs the 60s cooldown and 5/hour cap. */
    public function countRequestsSince(string $email, string $purpose, int $seconds): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM otp_codes
             WHERE email = :email AND purpose = :purpose
               AND TIMESTAMPDIFF(SECOND, created_at, NOW()) < :secs"
        );
        $stmt->execute(['email' => $email, 'purpose' => $purpose, 'secs' => $seconds]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * The most recent code still eligible to be checked against: not
     * consumed, not expired, attempts not exhausted. Returns null for any
     * dead state, so callers get one generic "invalid or expired" branch
     * instead of three.
     */
    public function findLatestActive(string $email, string $purpose): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT * FROM otp_codes
             WHERE email = :email AND purpose = :purpose
               AND consumed_at IS NULL AND expires_at > NOW() AND attempts_remaining > 0
             ORDER BY created_at DESC, id DESC LIMIT 1"
        );
        $stmt->execute(['email' => $email, 'purpose' => $purpose]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Burn one attempt after a wrong code. */
    public function decrementAttempts(string $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE otp_codes SET attempts_remaining = attempts_remaining - 1 WHERE id = :id"
        );
        return $stmt->execute(['id' => $id]);
    }

    /** Mark a code used so it can't be replayed. */
    public function markConsumed(string $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE otp_codes SET consumed_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
