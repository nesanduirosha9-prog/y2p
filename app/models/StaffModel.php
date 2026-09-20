<?php

namespace app\models;

use app\core\Database;
use PDO;

// StaffModel: the EER's single Staff entity — login credentials and academic
// catalog data in one table (replaces the old UserModel + LecturerModel's/
// InstructorModel's underlying `lecturers`/`instructors` tables, which used
// to be disconnected from login). Keyed by `code`, not a surrogate id.
class StaffModel
{
    /** Find a staff row by login email. */
    public function findByEmail(string $email)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Find a staff row by its badge code. */
    public function findByCode(string $code)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new staff login. The public signup form only collects
     * email/password (no name or badge code), so both are derived here —
     * `code` must be unique since it's the primary key.
     *
     * @return bool True on success, false on failure
     */
    public function create(string $email, string $password, string $role = 'timetable_officer'): bool
    {
        $pdo = Database::getConnection();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $localPart = strstr($email, '@', true) ?: $email;
        $name = ucwords(str_replace(['.', '_', '-'], ' ', $localPart));
        $code = $this->generateUniqueCode($localPart);

        $stmt = $pdo->prepare(
            "INSERT INTO staff (code, email, password, name, role)
             VALUES (:code, :email, :password, :name, :role)"
        );
        return $stmt->execute([
            'code' => $code,
            'email' => $email,
            'password' => $hashedPassword,
            'name' => $name,
            'role' => $role,
        ]);
    }

    /** Update a staff member's password by email. */
    public function updatePassword(string $email, string $newPassword): bool
    {
        $pdo = Database::getConnection();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE staff SET password = :password WHERE email = :email");
        return $stmt->execute([
            'email' => $email,
            'password' => $hashedPassword,
        ]);
    }

    /** Every staff member, most recent first. */
    public function getAllUsers(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT code, email, created_at FROM staff ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** A short, unique badge code derived from an email's local part. */
    private function generateUniqueCode(string $localPart): string
    {
        $pdo = Database::getConnection();
        $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $localPart));
        $base = substr($base !== '' ? $base : 'STF', 0, 3);

        $stmt = $pdo->prepare("SELECT 1 FROM staff WHERE code = :code");
        $code = $base;
        $suffix = 1;
        while (true) {
            $stmt->execute(['code' => $code]);
            if (!$stmt->fetch()) {
                return $code;
            }
            $code = $base . $suffix;
            $suffix++;
        }
    }
}
