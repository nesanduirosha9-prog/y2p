<?php

namespace app\models;

use app\core\Database;
use PDO;

// TimetableSessionModel: reads/writes the scheduled class sessions that
// render as blocks on the weekly grid. A session's key is now
// (room_code, day_of_week, start_hour) instead of a surrogate id — a room
// physically can't host two sessions at once, so that combination doubles
// as a hard "no double-booking" constraint at the database level.
class TimetableSessionModel
{
    /**
     * Sessions for a department + semester + year, as a flat list:
     *   [['day' => 'mon', 'start' => 8, 'duration' => 1, 'code' => 'CS1101',
     *     'title' => '...', 'location' => '...', 'type' => 'lab'], ...]
     *
     * Shape matches what timetable/index.php expects (was TimetableController::sessions()).
     * `location` is now the room's own code (joined from `rooms`).
     */
    public function forDeptSemYear(string $dept, int $sem, int $year): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT ts.day_of_week, ts.start_hour, ts.duration_hours,
                    r.code AS room_code, ts.session_type, c.code, c.title
             FROM timetable_sessions ts
             JOIN courses c ON c.code = ts.course_code
             JOIN rooms r   ON r.code = ts.room_code
             WHERE ts.department = :dept AND ts.semester = :sem AND ts.year_of_study = :year
             ORDER BY ts.day_of_week, ts.start_hour"
        );
        $stmt->execute(['dept' => $dept, 'sem' => $sem, 'year' => $year]);

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[] = [
                'day' => $row['day_of_week'],
                'start' => (int) $row['start_hour'],
                'duration' => (int) $row['duration_hours'],
                'code' => $row['code'],
                'title' => $row['title'],
                'location' => $row['room_code'],
                'type' => $row['session_type'],
            ];
        }
        return $out;
    }

    public function forStaffManager(string $staffCode): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT ts.course_code, c.title, ts.day_of_week, ts.start_hour, ts.duration_hours, ts.session_type
             FROM timetable_sessions ts
             JOIN courses c ON c.code = ts.course_code
             WHERE ts.managed_by_code = :staffCode
             ORDER BY ts.day_of_week, ts.start_hour"
        );
        $stmt->execute(['staffCode' => $staffCode]);

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[] = [
                'course_code'    => $row['course_code'],
                'course_name'    => $row['title'],
                'day_of_week'    => $row['day_of_week'],
                'start_hour'     => (int) $row['start_hour'],
                'duration_hours' => (int) $row['duration_hours'],
                'session_type'   => $row['session_type'],
            ];
        }
        return $out;
    }

    /**
     * Insert a scheduled session. $data keys:
     *   room_code, course_code, department, semester, year_of_study,
     *   day_of_week, start_hour, duration_hours, session_type, managed_by_code
     * Returns true on success; fails (unique constraint) if the room is
     * already booked for that day/hour.
     */
    public function create(array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO timetable_sessions
                (room_code, day_of_week, start_hour, course_code, department,
                 semester, year_of_study, duration_hours, session_type, managed_by_code)
             VALUES
                (:room_code, :day_of_week, :start_hour, :course_code, :department,
                 :semester, :year_of_study, :duration_hours, :session_type, :managed_by_code)"
        );
        return $stmt->execute([
            'room_code' => $data['room_code'],
            'day_of_week' => $data['day_of_week'],
            'start_hour' => $data['start_hour'],
            'course_code' => $data['course_code'],
            'department' => $data['department'],
            'semester' => $data['semester'],
            'year_of_study' => $data['year_of_study'],
            'duration_hours' => $data['duration_hours'],
            'session_type' => $data['session_type'],
            'managed_by_code' => $data['managed_by_code'] ?? null,
        ]);
    }



    public function session_request_create(array $data): bool
    {
    $pdo = Database::getConnection();

    try {
        // Begin transaction to ensure both parent request and child slots are saved together
        $pdo->beginTransaction();

        // 1. Insert the main request
        $stmt = $pdo->prepare(
            "INSERT INTO timetable_schedule_requests
                (course_code, requester_code, for_how_many_weeks, description)
             VALUES
                (:course_code, :requester_code, :for_how_many_weeks, :description)"
        );

        $stmt->execute([
            'course_code'        => $data['course_code'],
            'requester_code'     => $data['requester_code'],
            'for_how_many_weeks' => $data['for_how_many_weeks'],
            'description'        => $data['description'] ?? null,
        ]);

        // Retrieve the newly generated request ID
        $requestId = $pdo->lastInsertId();

        // 2. Insert all the associated slots
        $slotStmt = $pdo->prepare(
            "INSERT INTO timetable_schedule_request_slots
                (request_id, day_of_week, start_hour, duration_hours)
             VALUES
                (:request_id, :day_of_week, :start_hour, :duration_hours)"
        );

        foreach ($data['slots'] as $slot) {
            $slotStmt->execute([
                'request_id'     => $requestId,
                'day_of_week'    => $slot['day_of_week'],
                'start_hour'     => $slot['start_hour'],
                'duration_hours' => $slot['duration_hours'] ?? 1,
            ]);
        }

        // Commit transaction if everything succeeds
        $pdo->commit();

        return true;

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Failed to create schedule request: " . $e->getMessage());

        return false;
    }
    }



    public function getRequestsForStaff(string $staffCode): array
    {
    $pdo = \app\core\Database::getConnection();

    // Fetch both pending and approved requests.
    $stmt = $pdo->prepare("
        SELECT 
            r.course_code AS course_code,
            r.id AS request_id,
            r.status,
            r.description,
            r.for_how_many_weeks AS weeks,
            s.day_of_week,
            s.start_hour,
            s.duration_hours
        FROM timetable_schedule_requests r
        JOIN timetable_schedule_request_slots s 
            ON r.id = s.request_id
        WHERE r.requester_code = :staff_code
        ORDER BY s.day_of_week, s.start_hour
    ");

    $stmt->execute([
        'staff_code' => $staffCode
    ]);

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function session_request_update(array $data)
    {
    $pdo = Database::getConnection();

    try {
        // 1. Fetch the existing request so we can check it exists and who owns it
        $checkStmt = $pdo->prepare(
            "SELECT requester_code FROM timetable_schedule_requests WHERE id = :id"
        );
        $checkStmt->execute(['id' => $data['request_id']]);
        $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            return 'not_found';
        }

        // 2. Only the original requester may edit their own request.
        //    (If timetable_officer should be able to edit anyone's request,
        //    pass the caller's role into $data and bypass this check for that role.)
        if ($existing['requester_code'] !== $data['requester_code']) {
            return 'forbidden';
        }

        // 3. Build a partial UPDATE from only the fields that were actually sent
        $fields = [];
        $params = ['id' => $data['request_id']];

        if ($data['for_how_many_weeks'] !== null) {
            $fields[] = 'for_how_many_weeks = :for_how_many_weeks';
            $params['for_how_many_weeks'] = $data['for_how_many_weeks'];
        }

        if ($data['description'] !== null) {
            $fields[] = 'description = :description';
            $params['description'] = $data['description'];
        }

        if (empty($fields)) {
            // Nothing to update — treat as a no-op success
            return true;
        }

        $sql = "UPDATE timetable_schedule_requests SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return true;

    } catch (\Exception $e) {
        error_log("Failed to update schedule request: " . $e->getMessage());

        return false;
    }
    }



    
    public function session_request_delete(array $data)
    {
    $pdo = Database::getConnection();

    try {
        // 1. Fetch the existing request so we can check it exists and who owns it
        $checkStmt = $pdo->prepare(
            "SELECT requester_code FROM timetable_schedule_requests WHERE id = :id"
        );
        $checkStmt->execute(['id' => $data['request_id']]);
        $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            return 'not_found';
        }

        // 2. Only the original requester may delete their own request.
        //    (If timetable_officer should be able to delete anyone's request,
        //    pass the caller's role into $data and bypass this check for that role.)
        if ($existing['requester_code'] !== $data['requester_code']) {
            return 'forbidden';
        }

        // 3. Delete in a transaction: child slots first, then the parent request
        $pdo->beginTransaction();

        $slotStmt = $pdo->prepare(
            "DELETE FROM timetable_schedule_request_slots WHERE request_id = :id"
        );
        $slotStmt->execute(['id' => $data['request_id']]);

        $reqStmt = $pdo->prepare(
            "DELETE FROM timetable_schedule_requests WHERE id = :id"
        );
        $reqStmt->execute(['id' => $data['request_id']]);

        $pdo->commit();

        return true;

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Failed to delete schedule request: " . $e->getMessage());

        return false;
    }
    }
}
