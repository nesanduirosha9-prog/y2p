<?php

namespace app\models;

use app\core\Database;
use PDO;

// NotificationModel: the notification feed and its unread count (used for
// the sidebar badge on every dashboard screen). Every read is now scoped to
// a specific staff member via notification_recipients — a notification can
// be shared by many recipients, but each has their own is_read flag, unlike
// the old schema where is_read lived on the notification itself.
class NotificationModel
{
    /**
     * The feed for one staff member, newest first:
     *   [['type'=>'info', 'read'=>false, 'title'=>..., 'body'=>..., 'time'=>'10 minutes ago'], ...]
     */
    public function all(string $staffCode): array
    {
        $pdo = Database::getConnection();
        // Age is computed against the DB clock (TIMESTAMPDIFF) so it doesn't
        // depend on PHP and MySQL agreeing on a timezone.
        $stmt = $pdo->prepare(
            "SELECT n.type, n.title, n.body, nr.is_read,
                    TIMESTAMPDIFF(SECOND, n.created_at, NOW()) AS age_seconds
             FROM notification_recipients nr
             JOIN notifications n ON n.id = nr.notification_id
             WHERE nr.staff_code = :staff_code
             ORDER BY n.created_at DESC, n.id DESC"
        );
        $stmt->execute(['staff_code' => $staffCode]);

        return array_map(function ($r) {
            return [
                'type' => $r['type'],
                'read' => (bool) $r['is_read'],
                'title' => $r['title'],
                'body' => $r['body'],
                'time' => self::relativeTime((int) $r['age_seconds']),
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Count of unread notifications for one staff member. */
    public function unreadCount(string $staffCode): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM notification_recipients WHERE staff_code = :staff_code AND is_read = 0"
        );
        $stmt->execute(['staff_code' => $staffCode]);
        return (int) $stmt->fetchColumn();
    }

    /** Seconds-of-age -> "just now" / "10 minutes ago" / "3 days ago" / "5 weeks ago". */
    private static function relativeTime(int $ageSeconds): string
    {
        $ageSeconds = max(0, $ageSeconds);
        if ($ageSeconds < 60) {
            return 'just now';
        }
        $units = [
            ['week',   604800],
            ['day',    86400],
            ['hour',   3600],
            ['minute', 60],
        ];
        foreach ($units as [$name, $secs]) {
            if ($ageSeconds >= $secs) {
                $n = intdiv($ageSeconds, $secs);
                return $n . ' ' . $name . ($n === 1 ? '' : 's') . ' ago';
            }
        }
        return 'just now';
    }
}
