<?php

use app\core\Application;
// Auth layout: wraps individual view content for authentication pages. Expects `$content`, optional `$title`, and optional `$css_file`.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'UCSC Staff Management System' ?></title>
    
    <!-- Global CSS -->
    <link rel="stylesheet" href="/css/global.css">
    
    <!-- View-Specific CSS -->
    <?php if (isset($css_file)): ?>
        <link rel="stylesheet" href="<?= $css_file ?>">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- Main Content injected here -->
    <?= $content ?? '' ?>

</body>
</html>
