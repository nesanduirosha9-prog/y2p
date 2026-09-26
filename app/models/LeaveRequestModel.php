<?php

namespace app\models;

use app\core\Database;
use app\core\Uuid;
use PDO;
use PDOException;

// LeaveRequestModel: leave_requests + leave_days (migration 009).
//
// Leave is approved on paper outside the system, so there is no approval
// step here: a request is recorded as soon as it is saved. It counts as
// upcoming until its last day has passed, then as history.
//
// Staff side (instructor/LeaveController):
//   forRequester() read   create() create   updateUpcoming() update   deleteOwn() delete
// Coordinator / In-Charge side (coordinator/LeaveRequestsController):
//   all() read — the Upcoming / History tabs of the Leave Requests page
//
// Every record comes back in one shape, with its per-day covers attached:
//   ['id', 'requester_code', 'requester_name', 'requester_rank', 'leave_type',
//    'start_date', 'end_date', 'time_from', 'time_to', 'reason', 'created_at',
//    'days' => [['date', 'cover_code', 'cover_name'], ...]]
//
// Clashes (two leaves on one day for one person, one person covering two
// leaves on one day) are enforced by unique keys on leave_days. conflicts()
// checks the same rules up front so the user gets a readable message; the
// keys stay as the backstop for two submissions racing each other.
class LeaveRequestModel
{
    public const TYPES = ['sick', 'other'];

    /** Every request made by one staff member, newest first. */
    public function forRequester(string $code): array
    {
        return $this->fetch('WHERE r.requester_code = :code', ['code' => $code]);
    }

    /** Every request from every staff member, newest first. */
    public function all(): array
    {
        return $this->fetch('', []);
    }

    public function find(string $id): ?array
    {
        return $this->fetch('WHERE r.id = :id', ['id' => $id])[0] ?? null;
    }

    /**
     * Who may cover for $code: active academic staff of the same rank,
     * excluding $code. Also the whitelist the controller validates against.
     */
    public function coverCandidates(string $code): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT code, name FROM staff
             WHERE role = 'academic_staff' AND status = 'active' AND code <> :code
               AND academic_rank = (SELECT academic_rank FROM staff WHERE code = :code2)
             ORDER BY name"
        );
        $stmt->execute(['code' => $code, 'code2' => $code]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Readable reasons $days cannot be booked by $me; [] when they can.
     * $days = [['date' => 'Y-m-d', 'cover_code' => 'ABC'], ...].
     * $ignoreId is the request being edited, so it does not clash with itself.
     *
     * Checks, per date: $me is not already on leave, $me is not covering
     * someone, the cover is not covering someone else, the cover is not on
     * leave.
     */
    public function conflicts(string $me, array $days, ?string $ignoreId = null): array
    {
        $coverOn = array_column($days, 'cover_code', 'date');
        $dates = array_keys($coverOn);
        $in = implode(',', array_fill(0, count($dates), '?'));

        $stmt = Database::getConnection()->prepare(
            "SELECT d.leave_date, d.requester_code, d.cover_code,
                    rq.name AS requester_name, cv.name AS cover_name
             FROM leave_days d
             JOIN staff rq ON rq.code = d.requester_code
             JOIN staff cv ON cv.code = d.cover_code
             WHERE d.leave_id <> ? AND d.leave_date IN ($in)"
        );
        $stmt->execute(array_merge([$ignoreId ?? ''], $dates));

        $problems = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $on = date('D j M', strtotime($row['leave_date']));
            $cover = $coverOn[$row['leave_date']];

            if ($row['requester_code'] === $me) {
                $problems[] = "You already have leave on {$on}.";
            }
            if ($row['cover_code'] === $me) {
                $problems[] = "You are covering {$row['requester_name']} on {$on}.";
            }
            if ($row['cover_code'] === $cover) {
                $problems[] = "{$row['cover_name']} is already covering {$row['requester_name']} on {$on}.";
            }
            if ($row['requester_code'] === $cover) {
                $problems[] = "{$row['requester_name']} is on leave on {$on}.";
            }
        }
        return array_values(array_unique($problems));
    }

    /**
     * Records a request and its days in one transaction; returns the new id.
     * A clash that slipped past conflicts() surfaces as a PDOException with
     * SQLSTATE 23000 — the caller turns that into a 409.
     *
     * $d = ['leave_type', 'reason', 'time_from', 'time_to'] (reason and the
     * times may be null). $days must be sorted by date.
     */
    public function create(string $me, array $d, array $days): string
    {
        $pdo = Database::getConnection();
        $id = Uuid::v4();

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO leave_requests
                    (id, requester_code, leave_type, start_date, end_date, time_from, time_to, reason)
                 VALUES (:id, :me, :type, :start, :end, :tf, :tt, :reason)"
            )->execute($this->requestParams($d, $days) + ['id' => $id, 'me' => $me]);

            $this->insertDays($pdo, $id, $me, $days);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $id;
    }

    /**
     * Replaces a request's fields and days. Only the requester may do this,
     * and only before its first day; returns false otherwise. The row is
     * locked first so two edits of the same request cannot interleave.
     */
    public function updateUpcoming(string $id, string $me, array $d, array $days): bool
    {
        $pdo = Database::getConnection();

        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare(
                "SELECT 1 FROM leave_requests
                 WHERE id = :id AND requester_code = :me AND start_date > CURDATE() FOR UPDATE"
            );
            $lock->execute(['id' => $id, 'me' => $me]);
            if (!$lock->fetchColumn()) {
                $pdo->rollBack();
                return false;
            }

            $pdo->prepare(
                "UPDATE leave_requests
                 SET leave_type = :type, start_date = :start, end_date = :end,
                     time_from = :tf, time_to = :tt, reason = :reason
                 WHERE id = :id"
            )->execute($this->requestParams($d, $days) + ['id' => $id]);

            $pdo->prepare("DELETE FROM leave_days WHERE leave_id = :id")->execute(['id' => $id]);
            $this->insertDays($pdo, $id, $me, $days);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        return true;
    }

    /**
     * Cancel = delete. Only the requester's own request, and only before its
     * first day. leave_days go with it (ON DELETE CASCADE).
     */
    public function deleteOwn(string $id, string $me): bool
    {
        $stmt = Database::getConnection()->prepare(
            "DELETE FROM leave_requests
             WHERE id = :id AND requester_code = :me AND start_date > CURDATE()"
        );
        return $stmt->execute(['id' => $id, 'me' => $me]) && $stmt->rowCount() > 0;
    }

    // --- internals -----------------------------------------------------------

    private function requestParams(array $d, array $days): array
    {
        return [
            'type' => $d['leave_type'],
            'start' => $days[0]['date'],
            'end' => $days[count($days) - 1]['date'],
            'tf' => $d['time_from'],
            'tt' => $d['time_to'],
            'reason' => $d['reason'],
        ];
    }

    private function insertDays(PDO $pdo, string $id, string $me, array $days): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO leave_days (leave_id, requester_code, leave_date, cover_code)
             VALUES (:id, :me, :date, :cover)"
        );
        foreach ($days as $day) {
            $stmt->execute(['id' => $id, 'me' => $me, 'date' => $day['date'], 'cover' => $day['cover_code']]);
        }
    }

    /** Requests matching $where, each with its days attached. */
    private function fetch(string $where, array $params): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT r.id, r.requester_code, s.name AS requester_name, s.academic_rank AS requester_rank,
                    r.leave_type, r.start_date, r.end_date,
                    TIME_FORMAT(r.time_from, '%H:%i') AS time_from,
                    TIME_FORMAT(r.time_to, '%H:%i') AS time_to,
                    r.reason, r.created_at
             FROM leave_requests r
             JOIN staff s ON s.code = r.requester_code
             $where
             ORDER BY r.created_at DESC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === []) {
            return [];
        }

        $ids = array_column($rows, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $dayStmt = $pdo->prepare(
            "SELECT d.leave_id, d.leave_date, d.cover_code, c.name AS cover_name
             FROM leave_days d
             JOIN staff c ON c.code = d.cover_code
             WHERE d.leave_id IN ($in)
             ORDER BY d.leave_date"
        );
        $dayStmt->execute($ids);

        $days = [];
        foreach ($dayStmt->fetchAll(PDO::FETCH_ASSOC) as $day) {
            $days[$day['leave_id']][] = [
                'date' => $day['leave_date'],
                'cover_code' => $day['cover_code'],
                'cover_name' => $day['cover_name'],
            ];
        }
        foreach ($rows as &$row) {
            $row['days'] = $days[$row['id']] ?? [];
        }
        unset($row);

        return $rows;
    }
}
