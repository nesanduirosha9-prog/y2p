<?php

use app\core\Application;
// Main layout: wraps individual view content. Expects `$content` and optional `$title`.
// This is the framework's generic starter layout (PWA install banner, a
// plain navbar) — home/about/notfound/forbidden use it; every real
// StaffSync screen uses the 'auth' or 'dashboard' layout instead.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'My MVC App' ?></title>

    <link rel="stylesheet" href="/css/tokens.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/style.css">
    <!-- System toast (window.ttToast) -->
    <script src="/js/toast.js"></script>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1f2937">

    <!-- iOS-specific PWA meta tags (iOS ignores manifest.json mostly) -->
    <link rel="apple-touch-icon" href="/icons/apple-icon-180.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>

<body>

    <nav class="navbar">
        <div class="nav-brand">MyApp</div>
        <ul class="nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/about">About</a></li>
        </ul>
    </nav>

    <!-- Android install button (hidden until beforeinstallprompt fires) -->
    <button id="pwa-install-btn" class="btn install-btn hidden">
        📲 Install App
    </button>

    <!-- iOS banner with instructional video -->
    <div id="pwa-ios-banner" class="ios-banner hidden">
        <p>Install this app on your iPhone: tap <strong>Share</strong> then <strong>"Add to Home Screen"</strong>.</p>
        <!-- <video src="/videos/ios-install-guide.mp4" controls muted playsinline width="280"></video> -->
        <button class="ios-banner-close" onclick="this.parentElement.classList.add('hidden')">Dismiss</button>
    </div>

    <main class="container">
        <?= $content ?? '' ?>
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> My MVC App</p>
    </footer>

    <script src="/js/main.js"></script>
    <script src="/js/register-sw.js"></script>
    <script src="/js/pwa.js"></script>
</body>

</html>