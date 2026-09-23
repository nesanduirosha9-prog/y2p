<?php

namespace app\models;

use app\core\Database;
use PDO;

// StaffModel: the EER's single Staff entity — login credentials and academic
// catalog data in one table (replaces the old UserModel + LecturerModel's/
// InstructorModel's underlying `lecturers`/`instructors` tables, which used
// to be disconnected from login). Keyed by `code`, not a surrogate id.
//
// Since migration 016, a row can also be `status = 'pending'`: a self
// registration with no `role`/`academic_rank`/`position` yet, awaiting a
// Coordinator/In-Charge to assign one via the "Staff" approval screen.
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
     * Create a new (pending) staff login. The public signup form only
     * collects email/password — no name/phone/role/rank yet. `name` is
     * derived as a placeholder (the column is NOT NULL); the member fills
     * in their real name/phone from Settings once signed in, and a
     * Coordinator assigns a role later (see approve()).
     *
     * @return bool True on success, false on failure
     */
    public function create(string $email, string $password): bool
    {
        $pdo = Database::getConnection();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $localPart = strstr($email, '@', true) ?: $email;
        $name = ucwords(str_replace(['.', '_', '-'], ' ', $localPart));
        $code = $this->generateUniqueCode($localPart);

        $stmt = $pdo->prepare(
            "INSERT INTO staff (code, email, password, name, role, academic_rank, position, status)
             VALUES (:code, :email, :password, :name, NULL, NULL, NULL, 'pending')"
        );
        return $stmt->execute([
            'code' => $code,
            'email' => $email,
            'password' => $hashedPassword,
            'name' => $name,
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

    /**
     * Self-service profile update: a signed-in member may only change these
     * columns about themselves (not role/rank/position/department/
     * designation/email/etc — those are assigned by a Coordinator/In-Charge
     * via approve()/reassignPosition()/reassignTimetableOfficer()). The
     * array_intersect_key is the actual enforcement, independent of
     * whatever the calling controller trusts from the request body.
     */
    public function updateProfile(string $code, array $fields): bool
    {
        $editable = ['name', 'phone', 'office', 'extension', 'bio'];
        $data = array_intersect_key($fields, array_flip($editable));
        if (empty($data)) {
            return false;
        }

        $pdo = Database::getConnection();
        $set = implode(', ', array_map(fn($col) => "{$col} = :{$col}", array_keys($data)));
        $stmt = $pdo->prepare("UPDATE staff SET {$set} WHERE code = :code");
        return $stmt->execute($data + ['code' => $code]);
    }

    /** Every staff member, most recent first. */
    public function getAllUsers(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT code, email, created_at FROM staff ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Signups awaiting a Coordinator/In-Charge to assign a role, oldest first. */
    public function pendingRegistrations(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query(
            "SELECT code, name, email, phone, created_at
             FROM staff WHERE status = 'pending' ORDER BY created_at ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Every registered staff member (with assigned role), optionally filtered by role/rank/position label. */
    public function activeStaff(?string $roleFilter = null): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT s.code, s.name, s.email, s.phone, s.role, s.academic_rank, s.position,
                       s.department, s.designation, s.availability_status, s.status,
                       GROUP_CONCAT(DISTINCT cs.course_code ORDER BY cs.course_code SEPARATOR ',') AS assigned_courses
                FROM staff s
                LEFT JOIN course_staff cs ON cs.staff_code = s.code
                WHERE s.role IS NOT NULL";
        $params = [];

        if ($roleFilter === 'timetable_officer') {
            $sql .= " AND s.role = 'timetable_officer'";
        } elseif ($roleFilter === 'junior') {
            $sql .= " AND s.role = 'academic_staff' AND s.academic_rank = 'junior'";
        } elseif ($roleFilter === 'senior') {
            $sql .= " AND s.role = 'academic_staff' AND s.academic_rank = 'senior'";
        }

        $sql .= " GROUP BY s.code ORDER BY s.name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['courses'] = !empty($row['assigned_courses']) ? explode(',', $row['assigned_courses']) : [];
        }
        return $rows;
    }

    /**
     * Approve a pending registration: assigns role/rank and activates the
     * account. `$academicRank` is required (and only meaningful) when
     * `$role === 'academic_staff'`.
     */
    public function approve(string $code, string $role, ?string $academicRank): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE staff
             SET role = :role, academic_rank = :academic_rank, status = 'active'
             WHERE code = :code AND status = 'pending'"
        );
        return $stmt->execute([
            'role' => $role,
            'academic_rank' => $role === 'academic_staff' ? $academicRank : null,
            'code' => $code,
        ]) && $stmt->rowCount() > 0;
    }

    /** Reject (discard) a pending registration. */
    public function reject(string $code): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM staff WHERE code = :code AND status = 'pending'");
        return $stmt->execute(['code' => $code]) && $stmt->rowCount() > 0;
    }

    /**
     * The current holders of the three "key role" seats managed from the
     * In-Charge "Accounts" screen: the timetable officer, and every active
     * coordinator/in-charge. Used to render the Role Assignment table.
     */
    public function roleHolders(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query(
            "SELECT code, name, email, role, academic_rank, position
             FROM staff
             WHERE status = 'active' AND (role = 'timetable_officer' OR position IN ('coordinator', 'in_charge'))
             ORDER BY FIELD(position, 'in_charge', 'coordinator') , role DESC, name"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Active academic staff of a given rank, for the "search lecturer" replacement picker. */
    public function activeByRank(string $academicRank): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT code, name, email, position
             FROM staff
             WHERE status = 'active' AND role = 'academic_staff' AND academic_rank = :rank
             ORDER BY name"
        );
        $stmt->execute(['rank' => $academicRank]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reassign the `position` seat (coordinator/in_charge) from one staff
     * member to another. The caller must have already validated that
     * `$toCode` holds the matching `academic_rank` (coordinator -> junior,
     * in_charge -> senior) — the DB CHECK constraint enforces it either way.
     */
    public function reassignPosition(string $fromCode, string $toCode, string $position): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $clear = $pdo->prepare("UPDATE staff SET position = NULL WHERE code = :code AND position = :position");
            $clear->execute(['code' => $fromCode, 'position' => $position]);

            $set = $pdo->prepare("UPDATE staff SET position = :position WHERE code = :code");
            $set->execute(['position' => $position, 'code' => $toCode]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Hand the `timetable_officer` role itself to another staff member.
     * The outgoing officer needs a new role/rank since `role` is the
     * top-level ISA discriminator (not additive like `position`) —
     * `$fromNewRank` decides whether they fall back to junior or senior
     * academic staff.
     */
    public function reassignTimetableOfficer(string $fromCode, string $toCode, string $fromNewRank): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $demote = $pdo->prepare(
                "UPDATE staff SET role = 'academic_staff', academic_rank = :rank WHERE code = :code"
            );
            $demote->execute(['rank' => $fromNewRank, 'code' => $fromCode]);

            $promote = $pdo->prepare(
                "UPDATE staff SET role = 'timetable_officer', academic_rank = NULL, position = NULL WHERE code = :code"
            );
            $promote->execute(['code' => $toCode]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
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
