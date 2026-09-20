<?php

use app\core\Application;
// Dashboard layout: sidebar + header shell shared by all Instructor screens.
// Expects `$content`, optional `$title`, `$css_file`, and `$active` (nav key: timetable|workload|requests|leave|messages|settings).
$active = $active ?? 'timetable';
$userEmail = $_SESSION['user_email'] ?? 'tmf@ucsc.cmb.ac.lk';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'StaffSync - Instructor' ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/notifications.css">
    <?php foreach ((array)($css_file ?? []) as $cssHref): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($cssHref) ?>">
    <?php endforeach; ?>
</head>
<body>

    <div class="dashboard-shell">
        <aside class="dash-sidebar">
            <div class="sidebar-brand">
                <div class="brand-mark"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="brand-text">
                    <p class="brand-name">StaffSync</p>
                    <p class="brand-sub">Staff Portal</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <p class="nav-label">Navigation</p>
                <a href="/instructor/timetable" class="nav-item <?= $active === 'timetable' ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Timetable</span>
                </a>
                <a href="/instructor/workload" class="nav-item <?= $active === 'workload' ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Workload</span>
                </a>
                <a href="/instructor/requests" class="nav-item <?= $active === 'requests' ? 'active' : '' ?>">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Requests</span>
                </a>
                <a href="/instructor/leave" class="nav-item <?= $active === 'leave' ? 'active' : '' ?>">
                    <i class="fa-regular fa-calendar-minus"></i>
                    <span>Leave</span>
                </a>
                <a href="/instructor/messages" class="nav-item <?= $active === 'messages' ? 'active' : '' ?>">
                    <i class="fa-regular fa-message"></i>
                    <span>Messages</span>
                </a>

                <a href="/instructor/settings" class="nav-item <?= $active === 'settings' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gear"></i>
                    <span>Settings</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="/logout" class="nav-item nav-item-danger">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>

        <div class="dash-body">
            <header class="dash-header">
                <div class="header-titles">
                    <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                    <p><?= $pageSubtitle ?? 'University of Colombo' ?></p>
                </div>
                <div class="header-actions">
                    <button class="icon-btn" type="button" title="Toggle theme">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                    
                    <?php require_once \app\core\Application::$ROOT_DIR . '/views/components/notifications.php'; ?>

                    <div class="user-chip">
                        <div class="user-avatar" style="background: #4d179a;">IN</div>
                        <div class="user-meta">
                            <p class="user-name">Instructor</p>
                            <p class="user-email"><?= htmlspecialchars($userEmail) ?></p>
                        </div>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </header>

            <main class="dash-main">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <script src="/js/notifications.js"></script>
</body>
</html>
