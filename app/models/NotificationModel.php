<?php

namespace app\models;

use app\core\Database;
use PDO;

// NotificationModel: the notification feed and its unread count (used for the
// sidebar badge on every Timetable Officer screen).
class NotificationModel
{
    /**
     * The feed, newest first:
     *   [['type'=>'info', 'read'=>false, 'title'=>..., 'body'=>..., 'time'=>'10 minutes ago'], ...]
     */
    public function all(): array
    {
        $pdo = Database::getConnection();
        // Age is computed against the DB clock (TIMESTAMPDIFF) so it doesn't
        // depend on PHP and MySQL agreeing on a timezone.
        $rows = $pdo->query(
            "SELECT type, title, body, is_read,
                    TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
             FROM notifications
             ORDER BY created_at DESC, id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) {
            return [
                'type' => $r['type'],
                'read' => (bool) $r['is_read'],
                'title' => $r['title'],
                'body' => $r['body'],
                'time' => self::relativeTime((int) $r['age_seconds']),
            ];
        }, $rows);
    }

    /** Count of unread notifications. */
    public function unreadCount(): int
    {
        $pdo = Database::getConnection();
        return (int) $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")
            ->fetchColumn();
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
