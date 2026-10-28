<?php
/**
 * CASPA School OS – HTML layout header
 *
 * Usage at the top of every logged-in page:
 *   $pageTitle = 'Dashboard';        // required
 *   $pageRole  = 'admin';            // optional (defaults to user's session role)
 *   $activeMenu = 'Dashboard';       // optional menu active highlight override
 *   $breadcrumbs = [...];           // optional custom breadcrumbs array
 *   require_once ROOT . '/includes/layout_header.php';
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

require_once ROOT . '/includes/auth.php';

session_boot();
$user = current_user();

// Each page sets $pageTitle before including this file
$rawPageTitle = $pageTitle ?? 'Dashboard';
$pageTitleHtml = htmlspecialchars($rawPageTitle, ENT_QUOTES);
$docTitle = $pageTitleHtml . ' – CASPA School OS';
$userRole = $user['role'] ?? ($pageRole ?? 'guest');

// Centralized role menus array (as required)
$roleMenus = [
    'teacher' => [
        ['label' => 'Dashboard',   'icon' => 'bi-speedometer2',   'href' => APP_URL . '/pages/teacher/dashboard.php'],
        ['label' => 'My Classes',  'icon' => 'bi-easel',          'href' => APP_URL . '/pages/teacher/classes.php'],
        ['label' => 'Attendance',  'icon' => 'bi-calendar-check', 'href' => APP_URL . '/pages/teacher/attendance.php'],
        ['label' => 'Assignments', 'icon' => 'bi-journal-text',   'href' => APP_URL . '/pages/teacher/assignments.php'],
    ],
    'admin' => [
        ['label' => 'Dashboard',     'icon' => 'bi-speedometer2',  'href' => APP_URL . '/pages/admin/dashboard.php'],
        ['label' => 'Students',      'icon' => 'bi-mortarboard',   'href' => APP_URL . '/pages/admin/students.php'],
        ['label' => 'Classes',       'icon' => 'bi-building',      'href' => APP_URL . '/pages/admin/classes.php'],
        ['label' => 'Attendance Overview', 'icon' => 'bi-calendar-check', 'href' => APP_URL . '/pages/admin/attendance.php'],
        ['label' => 'Announcements', 'icon' => 'bi-megaphone',     'href' => APP_URL . '/pages/admin/announcements.php'],
    ],
    'principal' => [
        ['label' => 'Dashboard',           'icon' => 'bi-speedometer2',   'href' => APP_URL . '/pages/principal/dashboard.php'],
        ['label' => 'Students',            'icon' => 'bi-mortarboard',    'href' => APP_URL . '/pages/principal/students.php'],
        ['label' => 'Attendance Overview', 'icon' => 'bi-calendar-check', 'href' => APP_URL . '/pages/principal/attendance.php'],
        ['label' => 'Announcements',       'icon' => 'bi-megaphone',      'href' => APP_URL . '/pages/principal/announcements.php'],
    ],
    'student' => [
        ['label' => 'Dashboard',   'icon' => 'bi-speedometer2',   'href' => APP_URL . '/pages/student/dashboard.php'],
        ['label' => 'Assignments', 'icon' => 'bi-journal-text',   'href' => APP_URL . '/pages/student/assignments.php'],
        ['label' => 'Attendance',  'icon' => 'bi-calendar-check', 'href' => APP_URL . '/pages/student/attendance.php'],
    ],
    'parent' => [
        ['label' => 'Dashboard',         'icon' => 'bi-speedometer2',   'href' => APP_URL . '/pages/parent/dashboard.php'],
        ['label' => 'Child Attendance',  'icon' => 'bi-calendar-check', 'href' => APP_URL . '/pages/parent/attendance.php'],
        ['label' => 'Child Assignments', 'icon' => 'bi-journal-text',   'href' => APP_URL . '/pages/parent/assignments.php'],
        ['label' => 'Notices',           'icon' => 'bi-bell',           'href' => APP_URL . '/pages/parent/notices.php'],
    ],
];

// Determine menu items: page-level override if provided, else from roleMenus
$menuItems = (isset($sidebarItems) && is_array($sidebarItems)) ? $sidebarItems : ($roleMenus[$userRole] ?? []);

// Calculate active state
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
foreach ($menuItems as &$item) {
    $isActive = false;
    $itemScript = basename(parse_url($item['href'] ?? '', PHP_URL_PATH) ?? '');

    if (isset($item['active']) && $item['active']) {
        $isActive = true;
    } elseif (isset($activeMenu) && strcasecmp((string)$activeMenu, $item['label']) === 0) {
        $isActive = true;
    } elseif ($itemScript && $currentScript === $itemScript) {
        $isActive = true;
    } elseif (isset($pageTitle) && strcasecmp((string)$pageTitle, $item['label']) === 0) {
        $isActive = true;
    }

    $item['active'] = $isActive;
}
unset($item);

// Breadcrumbs setup
if (!isset($breadcrumbs) || !is_array($breadcrumbs)) {
    $roleName = ucfirst($userRole);
    $dashboardUrl = APP_URL . '/pages/' . $userRole . '/dashboard.php';
    if (strcasecmp($rawPageTitle, 'Dashboard') === 0) {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => $dashboardUrl],
            ['label' => $roleName . ' Dashboard', 'href' => ''],
        ];
    } else {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => $dashboardUrl],
            ['label' => $roleName, 'href' => $dashboardUrl],
            ['label' => $rawPageTitle, 'href' => ''],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CASPA School OS – role-based school management platform">
    <title><?= $docTitle ?></title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- CASPA App CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body class="caspa-body">

<!-- ===================== TOP NAV ===================== -->
<nav class="navbar navbar-expand caspa-navbar" id="topNav">
    <div class="container-fluid px-2 px-lg-3">
        <!-- Left: Sidebar toggles & Brand & Page Title -->
        <div class="d-flex align-items-center gap-2">
            <?php if ($user): ?>
            <!-- Mobile Offcanvas Toggler (< lg) -->
            <button
                class="btn btn-link text-white p-1 d-lg-none"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebarOffcanvas"
                aria-controls="sidebarOffcanvas"
                aria-label="Toggle navigation menu"
            >
                <i class="bi bi-list fs-4"></i>
            </button>

            <!-- Desktop Sidebar Collapse Toggle (>= lg) -->
            <button
                class="btn btn-link text-white p-1 d-none d-lg-inline-flex"
                id="sidebarToggleBtn"
                type="button"
                aria-label="Toggle sidebar collapse"
                title="Toggle sidebar"
            >
                <i class="bi bi-list fs-4"></i>
            </button>
            <?php endif; ?>

            <!-- Brand -->
            <a class="navbar-brand caspa-brand me-1 me-md-2" href="<?= APP_URL ?>">
                <span class="brand-icon">🎓</span>
                <span class="brand-text d-none d-sm-inline">CASPA <span class="brand-accent">School OS</span></span>
            </a>

            <!-- Page title in top bar -->
            <span class="vr mx-2 text-white-50 d-none d-md-inline-block" style="height: 1.5rem;"></span>
            <span class="topbar-page-title text-white fw-semibold d-none d-md-inline-block text-truncate" style="max-width: 260px;">
                <?= $pageTitleHtml ?>
            </span>
        </div>

        <!-- Right: Actions, CASPA AI button, User & Logout -->
        <div class="d-flex align-items-center gap-2 gap-sm-3 ms-auto">
            <!-- CASPA AI Button (Purple) -->
            <button
                type="button"
                class="btn btn-caspa-ai btn-sm d-flex align-items-center gap-1 shadow-sm px-2 px-sm-3"
                data-bs-toggle="offcanvas"
                data-bs-target="#caspaAiDrawer"
                aria-controls="caspaAiDrawer"
                aria-label="Open CASPA AI Assistant"
            >
                <i class="bi bi-stars"></i>
                <span class="fw-semibold">CASPA AI</span>
            </button>

            <?php if ($user): ?>
            <!-- Role Badge -->
            <span class="caspa-role-badge d-none d-sm-inline-block">
                <?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES) ?>
            </span>

            <!-- User dropdown -->
            <div class="dropdown">
                <button
                    class="btn btn-link dropdown-toggle caspa-user-menu text-decoration-none d-flex align-items-center gap-2 p-1"
                    type="button"
                    id="userDropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="d-none d-md-inline text-white fw-medium"><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                    <li class="px-3 py-2 d-md-none border-bottom">
                        <div class="fw-semibold"><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES) ?></div>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="<?= APP_URL ?>/api/auth/logout.php" id="logoutLink">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Log Out</span>
                        </a>
                    </li>
                </ul>
            </div>
            <?php else: ?>
            <a class="btn btn-caspa-primary btn-sm" href="<?= APP_URL ?>/index.php">Sign In</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ===================== CASPA AI DRAWER ===================== -->
<div class="offcanvas offcanvas-end caspa-ai-drawer" tabindex="-1" id="caspaAiDrawer" aria-labelledby="caspaAiDrawerLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title d-flex align-items-center text-white gap-2 mb-0" id="caspaAiDrawerLabel">
            <i class="bi bi-stars"></i>
            <span>CASPA AI Assistant</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-content-center align-items-center text-center p-4">
        <div class="ai-avatar-bubble mb-3">
            <i class="bi bi-stars fs-1" style="color: var(--color-purple);"></i>
        </div>
        <h4 class="fw-bold mb-1" style="color: var(--color-navy);">CASPA AI</h4>
        <span class="badge mb-3 px-3 py-2 text-white" style="background-color: var(--color-purple); font-size: 0.75rem; letter-spacing: 0.05em; text-transform: uppercase;">
            Preview
        </span>
        <div class="alert alert-light border w-100 py-3 mb-3 text-secondary">
            <p class="mb-0 fw-semibold">CASPA AI integration coming soon</p>
        </div>
        <p class="text-muted small mb-4">
            AI-powered school analytics, personalized student learning interventions, automated lesson planning, and attendance pattern insights will be available here.
        </p>
        <button type="button" class="btn btn-sm btn-outline-secondary px-4" data-bs-dismiss="offcanvas">
            Close
        </button>
    </div>
</div>

<!-- ===================== TOAST CONTAINER ===================== -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100;" aria-live="polite" aria-atomic="true"></div>

<!-- ===================== MAIN WRAPPER ===================== -->
<div class="caspa-wrapper">
<?php if ($user): ?>
<!-- Left Sidebar (Collapsible on Desktop, Offcanvas on Mobile) -->
<aside class="offcanvas-lg offcanvas-start caspa-sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
    <!-- Header for Mobile Offcanvas -->
    <div class="offcanvas-header d-lg-none border-bottom border-white-10 text-white p-3">
        <h5 class="offcanvas-title d-flex align-items-center gap-2 text-white mb-0" id="sidebarOffcanvasLabel">
            <span>🎓</span>
            <span>CASPA <span class="brand-accent">School OS</span></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Close"></button>
    </div>

    <!-- Sidebar Body -->
    <div class="offcanvas-body p-0 d-flex flex-column h-100">
        <nav class="sidebar-nav flex-grow-1 py-3" aria-label="Sidebar navigation">
            <ul class="nav flex-column" id="sidebarMenu">
                <?php foreach ($menuItems as $item): ?>
                <li class="nav-item">
                    <a
                        href="<?= htmlspecialchars($item['href'], ENT_QUOTES) ?>"
                        class="nav-link sidebar-link <?= $item['active'] ? 'active' : '' ?>"
                        <?= $item['active'] ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES) ?> me-2"></i>
                        <span><?= htmlspecialchars($item['label'], ENT_QUOTES) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Sidebar Bottom Logout Link -->
        <div class="p-3 border-top border-white-10 mt-auto">
            <a href="<?= APP_URL ?>/api/auth/logout.php" class="nav-link sidebar-link text-danger-subtle">
                <i class="bi bi-box-arrow-right me-2"></i>
                <span>Log Out</span>
            </a>
        </div>
    </div>
</aside>
<?php endif; ?>

<!-- Page content -->
<main class="caspa-main" id="mainContent">
    <div class="container-fluid py-3 py-md-4">
        <!-- Breadcrumbs -->
        <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb caspa-breadcrumb-nav mb-0">
                <?php foreach ($breadcrumbs as $idx => $bc): ?>
                    <?php if (!empty($bc['href']) && $idx < count($breadcrumbs) - 1): ?>
                        <li class="breadcrumb-item"><a href="<?= htmlspecialchars($bc['href'], ENT_QUOTES) ?>"><?= htmlspecialchars($bc['label'], ENT_QUOTES) ?></a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($bc['label'], ENT_QUOTES) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>
