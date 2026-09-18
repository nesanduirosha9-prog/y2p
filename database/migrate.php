<?php
/**
 * database/migrate.php — applies pending SQL migrations to the local database.
 *
 * Schema is defined by the .sql files in database/migrations/, applied in
 * filename order. Every applied file is recorded in the `schema_migrations`
 * table, so re-running only applies what is new.
 *
 * Usage:
 *   php database/migrate.php              apply pending migrations
 *   php database/migrate.php --status     list applied / pending, run nothing
 *   php database/migrate.php --seed       apply pending, then (re)run seeds/
 *   php database/migrate.php --fresh      DROP the database, recreate, apply all
 *   php database/migrate.php --fresh --seed   full rebuild with sample data
 *
 * Credentials come from ../config.php (copy config.php.example first).
 */

$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    fwrite(STDERR, "Missing config.php — copy config.php.example to config.php first.\n");
    exit(1);
}
require $configPath;

$host   = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$user   = defined('DB_USER') ? DB_USER : 'root';
$pass   = defined('DB_PASS') ? DB_PASS : '';
$dbName = defined('DB_NAME') ? DB_NAME : 'staffsync_db';

$args   = array_slice($argv, 1);
$seed   = in_array('--seed', $args, true);
$status = in_array('--status', $args, true);
$fresh  = in_array('--fresh', $args, true);

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Cannot connect to MySQL ({$user}@{$host}): {$e->getMessage()}\n");
    fwrite(STDERR, "Is MySQL running? For XAMPP: sudo /opt/lampp/lampp startmysql\n");
    exit(1);
}

if ($fresh) {
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    echo "Dropped database `$dbName`.\n";
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$dbName`");
$pdo->exec("
    CREATE TABLE IF NOT EXISTS schema_migrations (
        filename   VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$applied = array_flip(
    $pdo->query("SELECT filename FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN)
);

$migrations = glob(__DIR__ . '/migrations/*.sql');
sort($migrations);

if ($status) {
    echo "\nMigrations (" . count($migrations) . "):\n";
    foreach ($migrations as $file) {
        $name = basename($file);
        echo (isset($applied[$name]) ? "  [x] " : "  [ ] ") . $name . "\n";
    }
    echo "\n";
    exit(0);
}

$ran = 0;
foreach ($migrations as $file) {
    $name = basename($file);
    if (isset($applied[$name])) {
        continue;
    }
    echo "  applying  $name ... ";
    try {
        $pdo->exec(file_get_contents($file));
        $pdo->prepare("INSERT INTO schema_migrations (filename) VALUES (?)")->execute([$name]);
        echo "ok\n";
        $ran++;
    } catch (PDOException $e) {
        echo "FAILED\n";
        fwrite(STDERR, "    {$e->getMessage()}\n");
        exit(1);
    }
}
echo $ran ? "Applied $ran migration(s).\n" : "Nothing to migrate.\n";

if ($seed || $fresh) {
    $seeds = glob(__DIR__ . '/seeds/*.sql');
    sort($seeds);
    echo "\nSeeding (" . count($seeds) . "):\n";
    foreach ($seeds as $file) {
        echo "  seeding   " . basename($file) . " ... ";
        try {
            $pdo->exec(file_get_contents($file));
            echo "ok\n";
        } catch (PDOException $e) {
            echo "FAILED\n";
            fwrite(STDERR, "    {$e->getMessage()}\n");
            exit(1);
        }
    }
}

echo "\nDone.\n";
