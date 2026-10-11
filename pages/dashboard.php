<?php
// pages/dashboard.php - Initial/Common Application Screen & Workspace Dashboard
require_once __DIR__ . '/../config/session.php';
requireAuth();

$user = getCurrentUser();
$roleTitle = htmlspecialchars($user['active_role']['title'] ?? 'Principal');
$roleKey = htmlspecialchars($user['active_role']['key'] ?? 'principal');
$campusName = htmlspecialchars($user['active_campus']['name'] ?? 'Main Campus');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $roleTitle ?> Workspace - EduNEXA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/caspa-theme.css">
</head>
<body>

<!-- Header -->
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Body Layout -->
<div class="d-flex">
    <!-- Collapsible Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-grow-1 p-4 pb-5 mb-5 mb-lg-0 overflow-y-auto" style="min-height: calc(100vh - var(--topbar-height)); background-color: var(--caspa-surface);">
        <div class="container-fluid" style="max-width: 1300px;">
            
            <!-- Top Welcome Banner -->
            <div class="card border-0 shadow-sm p-4 mb-4 rounded-3 bg-white">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h1 class="h4 fw-bold text-dark mb-0">Welcome back, <?= htmlspecialchars($user['full_name']) ?> 👋</h1>
                            
                            <!-- PDF Section 4 UI Requirement: "Why can I see this? / Access Controlled" badge -->
                            <span class="badge bg-light text-dark border px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 11px; cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Why can I see this? Role: <?= $roleTitle ?>. Scope: Campus Assigned Records. Audit logging active.">
                                <i class="bi bi-shield-check" style="color: var(--caspa-teal);"></i>
                                <span>Access Controlled</span>
                                <i class="bi bi-info-circle text-muted" style="font-size: 10px;"></i>
                            </span>
                        </div>
                        <p class="text-muted small mb-0">
                            Active Campus: <strong style="color: var(--caspa-teal);"><?= $campusName ?></strong> • Role: <strong style="color: var(--caspa-navy);"><?= $roleTitle ?></strong>
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="index.php?page=choose-workspace" class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semibold">
                            <i class="bi bi-arrow-repeat me-1"></i> Switch Workspace Role
                        </a>
                        <button type="button" class="btn btn-caspa-purple btn-sm px-3 py-2 fw-semibold trigger-caspa">
                            <i class="bi bi-stars me-1"></i> Ask CASPA AI
                        </button>
                    </div>
                </div>
            </div>

            <!-- Role KPI Cards (PDF Section 5) -->
            <div class="mb-4">
                <div class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.8px;">
                    <?= strtoupper($roleTitle) ?> COMMAND OVERVIEW KPIS
                </div>

                <div class="row g-3">
                    <!-- KPI 1 -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center text-muted mb-2">
                                <span class="small fw-semibold">Campus Students</span>
                                <i class="bi bi-people fs-5" style="color: var(--caspa-teal);"></i>
                            </div>
                            <div class="h3 fw-bold text-dark mb-1">1,480</div>
                            <div class="small text-success fw-semibold d-flex align-items-center gap-1">
                                <i class="bi bi-arrow-up-right"></i> +94.2% Attendance Today
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2 -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center text-muted mb-2">
                                <span class="small fw-semibold">Faculty & Staff</span>
                                <i class="bi bi-mortarboard fs-5" style="color: var(--caspa-teal);"></i>
                            </div>
                            <div class="h3 fw-bold text-dark mb-1">124</div>
                            <div class="small text-muted">98 Present • 4 On Leave</div>
                        </div>
                    </div>

                    <!-- KPI 3 -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center text-muted mb-2">
                                <span class="small fw-semibold">Fee Collection (Term 1)</span>
                                <i class="bi bi-currency-dollar fs-5 text-success"></i>
                            </div>
                            <div class="h3 fw-bold text-dark mb-1">$142,500</div>
                            <div class="small text-warning fw-semibold">$12,400 Pending Review</div>
                        </div>
                    </div>

                    <!-- KPI 4 -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center text-muted mb-2">
                                <span class="small fw-semibold">Safety & Approvals</span>
                                <i class="bi bi-shield-check fs-5 text-success"></i>
                            </div>
                            <div class="h3 fw-bold text-dark mb-1">2 Pending</div>
                            <div class="small text-success fw-semibold">0 Critical Safety Alerts</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Foundation Status & Next Module Readiness Card -->
            <div class="card border-0 shadow-sm p-4 rounded-3" style="background-color: var(--caspa-teal-light); border-left: 4px solid var(--caspa-teal) !important;">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-2 rounded bg-white text-teal shadow-sm" style="color: var(--caspa-teal);">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                    <div>
                        <h2 class="h6 fw-bold mb-1" style="color: var(--caspa-navy);">
                            Initial Foundation & Common Application Shell Ready
                        </h2>
                        <p class="small mb-2" style="color: #174E5F; line-height: 1.6;">
                            The full authentication pipeline (Login, 2FA OTP verification, Forgot Password, Reset Password), Multi-Campus Selector, Role & Workspace Selector, Navigation Header, Left Sidebar, Mobile Bottom Navigation, Command Palette (Ctrl+K), and CASPA AI Assistant are functional and backed by PHP & PDO SQLite.
                        </p>
                        <div class="fw-bold small" style="color: var(--caspa-teal);">
                            📌 Standing by for next module Figma designs from user to proceed incrementally.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Mobile Bottom Navigation -->
<?php include __DIR__ . '/../includes/bottom-nav.php'; ?>

<!-- Modals & Drawers -->
<?php include __DIR__ . '/../includes/caspa-drawer.php'; ?>
<?php include __DIR__ . '/../includes/command-palette.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/auth.js"></script>
<script src="assets/js/app.js"></script>
<script>
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
</script>
</body>
</html>
