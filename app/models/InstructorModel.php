<?php

namespace app\models;

use app\core\Database;
use PDO;

// InstructorModel: teaching assistants for the instructor picker in the
// "Add / Edit Course" modal and the green badges on the Courses table.
class InstructorModel
{
    /** Every instructor as code => name, ordered by code. */
    public function all(): array
    {
        $pdo = Database::getConnection();
        $rows = $pdo->query("SELECT code, name FROM instructors ORDER BY code")
            ->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[$r['code']] = $r['name'];
        }
        return $out;
    }
}
