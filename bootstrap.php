<?php

// bootstrap.php — required by every entry point (app/public/index.php,
// database/migrate.php) before anything else runs.
// 1. Starts the PHP session (auth relies on $_SESSION).
// 2. Loads config.php (DB credentials — gitignored, machine-local).
// 3. Registers a PSR-4-like autoloader: `app\core\Foo` -> `app/core/Foo.php`.

// Start the session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/config.php';

// The department's clock. php.ini's date.timezone varies by machine (XAMPP
// ships Europe/Berlin), which put PHP's "today" a day behind MySQL's
// CURDATE() after midnight — leave showed a Cancel button the server then
// refused. Database::init() gives MySQL the same offset.
date_default_timezone_set('Asia/Colombo');

// Simple PSR-4-like autoloader for this mini framework.
// It converts namespace separators to directory separators
// and requires the corresponding PHP file if it exists.
spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class);
    $file = __DIR__ . '/' . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
