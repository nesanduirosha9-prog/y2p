<?php

namespace app\models;

use app\core\Database;
use PDO;

// LecturerModel: teaching staff for the "Lecturer Details" screen and the
// lecturer picker in the "Add / Edit Course" modal.
class LecturerModel
{
    /**
     * Every lecturer, keyed by code:
     *   ['DSC' => ['name'=>..., 'dept'=>..., 'email'=>..., 'courses'=>['CS1101', ...]], ...]
     */
    public function all(): array
    {
        $pdo = Database::getConnection();
        $rows = $pdo->query(
            "SELECT id, code, name, department, email FROM lecturers ORDER BY name"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return [];
        }

        $courses = $pdo->query(
            "SELECT cl.lecturer_id, c.code
             FROM course_lecturers cl
             JOIN courses c ON c.id = cl.course_id
             ORDER BY c.code"
        )->fetchAll(PDO::FETCH_ASSOC);

        $byLecturer = [];
        foreach ($courses as $row) {
            $byLecturer[(int) $row['lecturer_id']][] = $row['code'];
        }

        $out = [];
        foreach ($rows as $r) {
            $out[$r['code']] = [
                'name' => $r['name'],
                'dept' => $r['department'],
                'email' => $r['email'],
                'courses' => $byLecturer[(int) $r['id']] ?? [],
            ];
        }
        return $out;
    }
}
