<?php

namespace app\core;

use PDO;
use PDOException;

// Database: single shared PDO connection.
//
// The schema is NOT managed here anymore — it lives in database/migrations/
// and is applied with `php database/migrate.php`. This class just connects to
// a database that migrations already built.
class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::init();
        }
        return self::$pdo;
    }

    private static function init(): void
    {
        $host   = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $user   = defined('DB_USER') ? DB_USER : 'root';
        $pass   = defined('DB_PASS') ? DB_PASS : '';
        $dbname = defined('DB_NAME') ? DB_NAME : 'staffsync_db';

        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            self::$pdo = $pdo;
        } catch (PDOException $e) {
            die(
                "Database connection failed: " . $e->getMessage() . "\n\n" .
                "If the database or its tables are missing, set up the schema with:\n" .
                "    php database/migrate.php --seed\n"
            );
        }
    }
}
