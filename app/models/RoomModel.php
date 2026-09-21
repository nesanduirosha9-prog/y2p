<?php

namespace app\models;

use app\core\Database;
use PDO;

// RoomModel: reads/updates the room/lecture-hall catalog for the
// "Lecture Halls" screen. `code` is the room's primary key, so editing a
// room updates its capacity/type in place — the code itself isn't editable
// here (it's also the FK every timetable_session references).
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
}
