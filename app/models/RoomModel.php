<?php

namespace app\models;

use app\core\Database;
use PDO;

// RoomModel: full CRUD over the room/lecture-hall catalog for the
// "Lecture Halls" screen. `code` is the room's primary key, so editing a
// room updates its capacity/type in place — the code itself isn't editable
// here (it's also the FK every timetable_session references). Deleting is
// only safe when sessionCount() is 0: timetable_sessions.room_code is
// ON DELETE CASCADE, so the database would otherwise wipe those sessions.
class RoomModel
{
    /**
     * Every room, ordered by code:
     *   [['code' => 'LAB-A201', 'type' => 'lab', 'capacity' => 30], ...]
     */
    public function all(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT code, type, capacity FROM rooms ORDER BY code")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** True if a room with this code exists. */
    public function exists(string $code): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT 1 FROM rooms WHERE code = :code");
        $stmt->execute(['code' => $code]);
        return (bool) $stmt->fetchColumn();
    }

    /** Update a room's type/capacity. Returns false if the code doesn't exist. */
    public function update(string $code, string $type, int $capacity): bool
    {
        if (!$this->exists($code)) {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE rooms SET type = :type, capacity = :capacity WHERE code = :code");
        return $stmt->execute([
            'type' => $type,
            'capacity' => $capacity,
            'code' => $code,
        ]);
    }

    /** Insert a new room. Caller must check exists() first — a duplicate code throws. */
    public function create(string $code, string $type, int $capacity): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO rooms (code, type, capacity) VALUES (:code, :type, :capacity)");
        return $stmt->execute([
            'code' => $code,
            'type' => $type,
            'capacity' => $capacity,
        ]);
    }

    /** How many timetable sessions use this room — deleting it would cascade to all of them. */
    public function sessionCount(string $code): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable_sessions WHERE room_code = :code");
        $stmt->execute(['code' => $code]);
        return (int) $stmt->fetchColumn();
    }

    /** Delete a room. Returns false if no row matched. */
    public function delete(string $code): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE code = :code");
        return $stmt->execute(['code' => $code]) && $stmt->rowCount() > 0;
    }
}
