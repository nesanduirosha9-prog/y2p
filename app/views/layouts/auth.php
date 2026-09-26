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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

    <!-- Shared design tokens / components, then global, then page-specific -->
    <link rel="stylesheet" href="/css/tokens.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/global.css">

    <!-- View-Specific CSS -->
    <?php if (isset($css_file)): ?>
        <link rel="stylesheet" href="<?= $css_file ?>">
    <?php endif; ?>

    <!-- System toast (window.ttToast) -->
    <script src="/js/toast.js"></script>
</head>
<body>

    <!-- Main Content injected here -->
    <?= $content ?? '' ?>

    <script src="/js/password_toggle.js"></script>

</body>
</html>
