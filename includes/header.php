<?php
// includes/header.php - Top Navigation Bar
$user = getCurrentUser();
$campusName = htmlspecialchars($user['active_campus']['name'] ?? 'Main Campus');
$roleTitle = htmlspecialchars($user['active_role']['title'] ?? 'Principal');
$initials = strtoupper(substr($user['full_name'] ?? 'US', 0, 2));
?>
<header class="d-flex align-items-center justify-content-between px-4 bg-white border-bottom sticky-top" style="height: var(--topbar-height); z-index: 1020;">
    <!-- Left: Brand & Campus Selector -->
    <div class="d-flex align-items-center gap-3">
        <a href="index.php?page=dashboard" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="brand-badge-box bg-dark text-white" style="width: 32px; height: 32px; font-size: 13px;">EN</div>
            <span class="fw-bold fs-5" style="color: var(--caspa-navy);">EduNexa</span>
        </a>

        <!-- Campus Switcher Pill -->
        <a href="index.php?page=select-campus" class="btn btn-sm btn-light border d-flex align-items-center gap-2 px-2 py-1 text-dark text-decoration-none" style="font-size: 12px; font-weight: 600;">
            <i class="bi bi-building" style="color: var(--caspa-teal);"></i>
            <span><?= $campusName ?></span>
            <i class="bi bi-chevron-down text-muted" style="font-size: 10px;"></i>
        </a>
    </div>

    <!-- Center: Command Search Bar (Ctrl+K) -->
    <div class="d-none d-md-flex align-items-center flex-grow-1 mx-4" style="max-width: 440px;">
        <button type="button" id="btnOpenCommandSearch" class="btn btn-light border w-100 rounded-pill d-flex align-items-center justify-content-between px-3 py-1 text-muted" style="font-size: 13px;">
            <span class="d-flex align-items-center gap-2">
                <i class="bi bi-search" style="color: var(--caspa-teal);"></i>
                <span>Search modules, students, commands...</span>
            </span>
            <span class="badge bg-white text-dark border px-2 py-1" style="font-size: 10px;">Ctrl K</span>
        </button>
    </div>

    <!-- Right: Workspace Switcher, CASPA AI, Notifications & Profile -->
    <div class="d-flex align-items-center gap-2">
        <!-- Active Workspace Switcher -->
        <a href="index.php?page=choose-workspace" class="btn btn-sm px-2 py-1 d-flex align-items-center gap-1 text-decoration-none fw-bold" style="background-color: var(--caspa-teal-light); color: var(--caspa-teal); border: 1px solid var(--caspa-teal); font-size: 12px;" title="Switch Role Workspace">
            <i class="bi bi-arrow-repeat"></i>
            <span><?= $roleTitle ?></span>
        </a>

        <!-- CASPA AI Assistant Trigger -->
        <button type="button" id="btnToggleCaspa" class="btn btn-caspa-purple d-flex align-items-center gap-2 shadow-sm" style="font-size: 13px;">
            <i class="bi bi-stars"></i>
            <span class="d-none d-sm-inline">CASPA AI</span>
        </button>

        <!-- Notification Bell -->
        <button type="button" class="btn btn-light border rounded-circle position-relative p-2" style="width: 36px; height: 36px;">
            <i class="bi bi-bell text-dark"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
        </button>

        <!-- User Dropdown Menu -->
        <div class="dropdown">
            <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 36px; height: 36px; background-color: var(--caspa-navy); font-size: 13px;">
                    <?= $initials ?>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border p-2" style="min-width: 200px;">
                <li class="px-3 py-2 border-bottom mb-1">
                    <div class="fw-bold small text-dark"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($user['email']) ?></div>
                </li>
                <li><a class="dropdown-item small py-2" href="index.php?page=choose-workspace"><i class="bi bi-arrow-repeat me-2"></i> Switch Role</a></li>
                <li><a class="dropdown-item small py-2" href="index.php?page=select-campus"><i class="bi bi-building me-2"></i> Switch Campus</a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item small py-2 text-danger btn-action-logout" href="#"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
            </ul>
        </div>
    </div>
</header>
