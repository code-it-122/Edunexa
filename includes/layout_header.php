<?php
/**
 * CASPA School OS – HTML layout header
 *
 * Usage at the top of every page:
 *   $pageTitle = 'Dashboard';        // required
 *   $pageRole  = 'admin';            // required – drives active nav
 *   require_once ROOT . '/includes/layout_header.php';
 *
 * ROOT is defined in each page as:
 *   define('ROOT', dirname(__DIR__, N));
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

require_once ROOT . '/includes/auth.php';

// Each page sets $pageTitle before including this file.
$pageTitle = isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES) . ' – CASPA School OS' : 'CASPA School OS';
$user      = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CASPA School OS – role-based school management platform">
    <title><?= $pageTitle ?></title>

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
<nav class="navbar navbar-expand-lg caspa-navbar" id="topNav">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand caspa-brand" href="<?= APP_URL ?>">
            <span class="brand-icon">🎓</span>
            <span class="brand-text">CASPA <span class="brand-accent">School OS</span></span>
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNav"
            aria-controls="mainNav"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Right side -->
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <?php if ($user): ?>
                <li class="nav-item">
                    <span class="nav-link caspa-role-badge">
                        <?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES) ?>
                    </span>
                </li>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle caspa-user-menu"
                        href="#"
                        id="userDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($user['name'], ENT_QUOTES) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="<?= APP_URL ?>/pages/<?= htmlspecialchars($user['role'], ENT_QUOTES) ?>/profile.php">
                                <i class="bi bi-person me-2"></i>My Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= APP_URL ?>/api/auth/logout.php" id="logoutLink">
                                <i class="bi bi-box-arrow-right me-2"></i>Log Out
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="btn btn-caspa-primary btn-sm" href="<?= APP_URL ?>/index.php">Sign In</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===================== TOAST CONTAINER ===================== -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100;" aria-live="polite" aria-atomic="true"></div>

<!-- ===================== MAIN WRAPPER ===================== -->
<div class="caspa-wrapper">
<?php if (isset($pageRole) && $user): ?>
<!-- Sidebar -->
<aside class="caspa-sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav flex-column" id="sidebarMenu">
            <!-- Populated per-role by each page (inject $sidebarItems before header) -->
            <?php if (isset($sidebarItems) && is_array($sidebarItems)): ?>
                <?php foreach ($sidebarItems as $item): ?>
                <li class="nav-item">
                    <a
                        href="<?= htmlspecialchars($item['href'], ENT_QUOTES) ?>"
                        class="nav-link sidebar-link <?= isset($item['active']) && $item['active'] ? 'active' : '' ?>"
                    >
                        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES) ?> me-2"></i>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
<!-- /Sidebar -->
<?php endif; ?>

<!-- Page content -->
<main class="caspa-main" id="mainContent">
    <div class="container-fluid py-4">
