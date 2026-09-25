<?php

use app\core\Application;

// Dashboard layout: sidebar + header shell shared by every role.
// Replaces timetable_officer_dashboard.php + instructor_dashboard.php,
// which were ~90% identical markup — this branches on $_SESSION['role']
// for the handful of things that actually differ (nav items, user chip,
// role-specific asset includes) and writes everything else once.
//
// Expects `$content`, optional `$title`, `$css_file`, `$active` (nav key),
// `$pageTitle` (a short name for the top bar).

$role = $_SESSION['role'] ?? 'timetable_officer';
$position = $_SESSION['position'] ?? null; // additive: 'coordinator' | 'in_charge' | null
$academicRank = $_SESSION['academic_rank'] ?? null;
$isInstructor = $role === 'academic_staff';
$active = $active ?? 'timetable';
$userEmail = $_SESSION['user_email'] ?? ($isInstructor ? 'tmf@ucsc.cmb.ac.lk' : 'tmo@ucsc.cmb.ac.lk');

// Sidebar, as groups. The ordering rules live in the structure, so appending
// an item cannot break them:
//   - personal pages first, administrative ones after (Coordinator / In-Charge);
//   - Messages and Settings are always the last two, for every role.
$nav = fn($href, $icon, $label, $key) => compact('href', 'icon', 'label', 'key');
$navGroups = [];

if (!$isInstructor) {
    $navGroups[] = ['label' => 'Navigation', 'items' => [
        $nav('/timetable',     'fa-solid fa-calendar-days', 'Timetable',      'timetable'),
        $nav('/courses',       'fa-solid fa-book-open',     'Course Details', 'courses'),
        $nav('/staff',         'fa-solid fa-users',         'Staff Details',  'lecturers'),
        $nav('/lecture-halls', 'fa-solid fa-building',      'Lecture Halls',  'lecture-halls'),
    ]];
} else {
    // Academic Staff: Lecturers (senior) see My Courses; Instructors (junior) see My Workload
    $own = [$nav('/timetable', 'fa-solid fa-calendar-days', 'Timetable', 'timetable')];
    $own[] = ($academicRank === 'senior' || $position === 'in_charge')
        ? $nav('/courses',  'fa-solid fa-book-open',   'My Courses',  'courses')
        : $nav('/workload', 'fa-solid fa-layer-group', 'My Workload', 'workload');
    $own[] = $nav('/leave', 'fa-regular fa-calendar-minus', 'Leave', 'leave');

    $admin = [];
    if ($position === 'coordinator' || $position === 'in_charge') {
        // Same screen for both positions; the Coordinator also gets the duty
        // tabs (This week / Requests / Who's free) on it.
        $admin[] = $nav('/workload/distribution', 'fa-solid fa-table-cells', 'Workload', 'workload-dist');
        $admin[] = $nav('/evaluations', 'fa-solid fa-clipboard-check', 'Evaluations',   'evaluations');
        $admin[] = $nav('/staff',       'fa-solid fa-user-check',      'Staff Details', 'staff');
        // The department-wide log is rarely needed, so it closes the admin
        // group. Every role can still read its OWN log from the profile menu
        // (components/user_chip.php); these two do not see the same rows — see
        // AuditController.
        $admin[] = $nav('/audit', 'fa-solid fa-clock-rotate-left', 'Activity Log', 'audit');
    }

    // With a single group a "My work" heading would be noise.
    $navGroups[] = ['label' => $admin ? 'My work' : 'Navigation', 'items' => $own];
    if ($admin) {
        $navGroups[] = ['label' => 'Administration', 'items' => $admin];
    }
}

// Always last, always in this order, for every role.
$navGroups[] = ['label' => null, 'items' => [
    $nav('/messages', 'fa-regular fa-message', 'Messages', 'messages'),
    $nav('/settings', 'fa-solid fa-gear',      'Settings', 'settings'),
]];

// The profile chip builds itself from ViewHelpers (the signed-in member's name
// and 3-letter code) — see components/user_chip.php. It used to show a role
// title computed here; only $userEmail is still passed down.
$titleSuffix = $isInstructor ? 'StaffSync - Instructor' : 'StaffSync';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? $titleSuffix ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="/css/tokens.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/notifications.css">
    <?php if ($isInstructor): ?>
        <link rel="stylesheet" href="/css/instructor/common.css">
    <?php endif; ?>
    <?php foreach ((array)($css_file ?? []) as $cssHref): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($cssHref) ?>">
    <?php endforeach; ?>
    <!-- In <head>, not beside dashboard.js: page scripts inside $content call codeBadge() while they render. -->
    <script src="/js/code_badge.js"></script>
</head>
<body>

    <div class="dashboard-shell">
        <div class="dash-sidebar-backdrop" id="sidebarBackdrop"></div>
        <aside class="dash-sidebar" id="dashSidebar">
            <div class="sidebar-brand">
                <div class="brand-mark"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="brand-text">
                    <p class="brand-name">StaffSync</p>
                    <p class="brand-sub">Staff Portal</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($navGroups as $group): ?>
                    <div class="nav-group <?= $group['label'] === null ? 'nav-group-tail' : '' ?>">
                        <?php if ($group['label'] !== null): ?>
                            <p class="nav-label"><?= htmlspecialchars($group['label']) ?></p>
                        <?php endif; ?>
                        <?php foreach ($group['items'] as $item): ?>
                            <a href="<?= htmlspecialchars($item['href']) ?>" class="nav-item <?= $active === $item['key'] ? 'active' : '' ?>">
                                <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                                <span><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
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
                    <button class="icon-btn sidebar-toggle" type="button" title="Menu" id="sidebarToggleBtn">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                    </div>
                </div>
                <div class="header-actions">
                    <!-- Decorative only right now — no click handler / dark theme exists yet (see gaps) -->
                    <button class="icon-btn" type="button" title="Toggle theme">
                        <i class="fa-solid fa-moon"></i>
                    </button>

                    <?php require_once \app\core\Application::$ROOT_DIR . '/views/components/notifications.php'; ?>

                    <?php require \app\core\Application::$ROOT_DIR . '/views/components/user_chip.php'; ?>
                </div>
            </header>

            <main class="dash-main">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <script src="/js/notifications.js"></script>
    <script src="/js/dashboard.js"></script>
    <?php if ($isInstructor): ?>
        <script src="/js/instructor/common.js"></script>
    <?php endif; ?>
</body>
</html>
