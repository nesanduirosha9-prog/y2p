<?php

use app\core\Application;

// Dashboard layout: sidebar + header shell shared by every role.
// Replaces timetable_officer_dashboard.php + instructor_dashboard.php,
// which were ~90% identical markup — this branches on $_SESSION['role']
// for the handful of things that actually differ (nav items, user chip,
// role-specific asset includes) and writes everything else once.
//
// Expects `$content`, optional `$title`, `$css_file`, `$active` (nav key),
// `$pageTitle`, `$pageSubtitle`.

$role = $_SESSION['role'] ?? 'timetable_officer';
$position = $_SESSION['position'] ?? null; // additive: 'coordinator' | 'in_charge' | null
$academicRank = $_SESSION['academic_rank'] ?? null;
$isInstructor = $role === 'academic_staff';
$active = $active ?? 'timetable';
$userEmail = $_SESSION['user_email'] ?? ($isInstructor ? 'tmf@ucsc.cmb.ac.lk' : 'tmo@ucsc.cmb.ac.lk');

// Base nav items per role
$navItemsByRole = [
    'timetable_officer' => [
        ['href' => '/timetable',      'icon' => 'fa-solid fa-calendar-days', 'label' => 'Timetable',      'key' => 'timetable'],
        ['href' => '/course-details', 'icon' => 'fa-solid fa-book-open',     'label' => 'Course Details', 'key' => 'courses'],
        ['href' => '/lecturers',      'icon' => 'fa-solid fa-users',         'label' => 'Staff Details',  'key' => 'lecturers'],
        ['href' => '/lecture-halls',  'icon' => 'fa-solid fa-building',      'label' => 'Lecture Halls',  'key' => 'lecture-halls'],
    ],
    'academic_staff' => [
        ['href' => '/instructor/timetable',  'icon' => 'fa-solid fa-calendar-days', 'label' => 'Timetable',   'key' => 'timetable'],
        ['href' => '/instructor/workload',   'icon' => 'fa-solid fa-layer-group',   'label' => 'My Workload', 'key' => 'workload'],
        ['href' => '/instructor/my-courses', 'icon' => 'fa-solid fa-book-open',     'label' => 'My Courses',  'key' => 'courses'],
    ],
];
$navItems = $navItemsByRole[$isInstructor ? 'academic_staff' : 'timetable_officer'];

// Role-specific Workload & Evaluation extensions
if ($isInstructor) {
    if ($position === 'coordinator') {
        $navItems[] = ['href' => '/coordinator/workload/distribution', 'icon' => 'fa-solid fa-table-cells',     'label' => 'Workload Matrix', 'key' => 'workload-dist'];
        $navItems[] = ['href' => '/coordinator/workload/scheduler',    'icon' => 'fa-solid fa-calendar-check',  'label' => 'Duty Scheduler',  'key' => 'workload-sched'];
        $navItems[] = ['href' => '/coordinator/staff',                 'icon' => 'fa-solid fa-user-check',      'label' => 'Staff',           'key' => 'staff'];
        $navItems[] = ['href' => '/coordinator/evaluations',           'icon' => 'fa-solid fa-clipboard-check', 'label' => 'Evaluations',     'key' => 'evaluations'];
    } elseif ($position === 'in_charge') {
        $navItems[] = ['href' => '/in-charge/workload/distribution',    'icon' => 'fa-solid fa-table-cells',     'label' => 'Workload Matrix', 'key' => 'workload-dist'];
        $navItems[] = ['href' => '/coordinator/staff',                 'icon' => 'fa-solid fa-user-check',      'label' => 'Staff',           'key' => 'staff'];
        $navItems[] = ['href' => '/in-charge/accounts',                'icon' => 'fa-solid fa-people-arrows',   'label' => 'Accounts',        'key' => 'accounts'];
        $navItems[] = ['href' => '/in-charge/evaluations',             'icon' => 'fa-solid fa-award',           'label' => 'Appraisals',      'key' => 'evaluations'];
    } elseif ($academicRank === 'senior') {
        // Lecturer-in-charge
        $navItems[] = ['href' => '/instructor/evaluations',            'icon' => 'fa-solid fa-star-half-stroke', 'label' => 'Evaluations',     'key' => 'evaluations'];
    } else {
        // Junior Staff / Instructor
        $navItems[] = ['href' => '/instructor/evaluations',            'icon' => 'fa-solid fa-star-half-stroke', 'label' => 'Evaluations',     'key' => 'evaluations'];
    }

    $navItems[] = ['href' => '/instructor/leave',    'icon' => 'fa-regular fa-calendar-minus', 'label' => 'Leave',    'key' => 'leave'];
    $navItems[] = ['href' => '/instructor/messages', 'icon' => 'fa-regular fa-message',        'label' => 'Messages', 'key' => 'messages'];
}

$navItems[] = $isInstructor
    ? ['href' => '/instructor/settings', 'icon' => 'fa-solid fa-gear', 'label' => 'Settings', 'key' => 'settings']
    : ['href' => '/settings',            'icon' => 'fa-solid fa-gear', 'label' => 'Settings', 'key' => 'settings'];

$userChipLabel = $isInstructor ? ($position ? ucwords(str_replace('_', ' ', $position)) : ($academicRank === 'senior' ? 'Lecturer' : 'Instructor')) : 'Timetable Officer';
$userAvatarInitials = $isInstructor ? 'IN' : 'TO';
$userAvatarStyle = $isInstructor ? 'style="background: #4d179a;"' : '';
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
                <p class="nav-label">Navigation</p>
                <?php foreach ($navItems as $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="nav-item <?= $active === $item['key'] ? 'active' : '' ?>">
                        <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
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
                        <p><?= $pageSubtitle ?? 'University of Colombo' ?></p>
                    </div>
                </div>
                <div class="header-actions">
                    <!-- Decorative only right now — no click handler / dark theme exists yet (see gaps) -->
                    <button class="icon-btn" type="button" title="Toggle theme">
                        <i class="fa-solid fa-moon"></i>
                    </button>

                    <?php require_once \app\core\Application::$ROOT_DIR . '/views/components/notifications.php'; ?>

                    <div class="user-chip">
                        <div class="user-avatar" <?= $userAvatarStyle ?>><?= $userAvatarInitials ?></div>
                        <div class="user-meta">
                            <p class="user-name"><?= htmlspecialchars($userChipLabel) ?></p>
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
    <script src="/js/dashboard.js"></script>
    <?php if ($isInstructor): ?>
        <script src="/js/instructor/common.js"></script>
    <?php endif; ?>
</body>
</html>
