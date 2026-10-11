<?php
// pages/choose-workspace.php - Matching Figma 'login' Workspace Selector Screen
require_once __DIR__ . '/../config/session.php';
requireAuth();

$pdo = getDbConnection();
$user = getCurrentUser();
$activeRoleKey = $user['active_role']['key'] ?? 'principal';
$activeCampusName = strtoupper($user['active_campus']['name'] ?? 'MAIN CAMPUS');
$activeCampusCode = strtoupper($user['active_campus']['code'] ?? 'CAMP-01');

// Fetch user roles
$stmt = $pdo->prepare("
    SELECT r.key, r.title, r.description, r.category
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$userRoles = $stmt->fetchAll();

if (empty($userRoles)) {
    $userRoles = [
        ['key' => 'principal', 'title' => 'Principal', 'description' => 'School command center, approvals, academics & finance'],
        ['key' => 'admin', 'title' => 'Admin', 'description' => 'Students, parents, classes, operations & RBAC'],
        ['key' => 'teacher', 'title' => 'Teacher', 'description' => 'Classes, attendance, lesson plans, homework & marks'],
        ['key' => 'student', 'title' => 'Student', 'description' => 'Learning, timetable, assignments, exams & CASPA AI'],
        ['key' => 'parent', 'title' => 'Parent', 'description' => 'Children progress, attendance, fees & notices'],
        ['key' => 'safety', 'title' => 'Safety Officer', 'description' => 'Confidential incidents, investigation & safeguarding']
    ];
}

$roleIcons = [
    'principal' => 'bi-mortarboard',
    'admin' => 'bi-shield-shaded',
    'teacher' => 'bi-book',
    'student' => 'bi-person-badge',
    'parent' => 'bi-people',
    'hr' => 'bi-briefcase',
    'accounts' => 'bi-receipt',
    'safety' => 'bi-shield-check',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Workspace - EduNEXA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/caspa-theme.css">
</head>
<body class="bg-light">

<div class="selection-container">
    <!-- Top Header -->
    <header class="selection-top-bar">
        <div class="auth-brand-logo text-dark">
            <div class="brand-badge-box bg-dark text-white">EN</div>
            <span style="color: var(--caspa-navy);">EduNexa</span>
        </div>
        <div class="d-flex align-items-center gap-2 small text-muted fw-semibold">
            <i class="bi bi-shield-check text-success fs-6"></i>
            <span>Secure workspace access</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="selection-main-body">
        <div class="selection-crumb">
            ‹ <?= htmlspecialchars($activeCampusName) ?> - <?= htmlspecialchars($activeCampusCode) ?>
        </div>
        <h1 class="selection-head">Choose your workspace</h1>
        <p class="selection-sub">You have access to more than one role at this school.</p>

        <!-- Role Workspaces Grid -->
        <div class="grid-cards">
            <?php foreach ($userRoles as $r): 
                $isSelected = ($r['key'] === $activeRoleKey);
                $iconClass = $roleIcons[$r['key']] ?? 'bi-person-workspace';
            ?>
                <div class="selection-item-card workspace-card-select <?= $isSelected ? 'active-selected' : '' ?>" data-role="<?= htmlspecialchars($r['key']) ?>">
                    <div class="card-icon-box">
                        <i class="bi <?= $iconClass ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold fs-6" style="color: var(--caspa-text);"><?= htmlspecialchars($r['title']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($r['description']) ?></div>
                    </div>
                    <?php if ($isSelected): ?>
                        <i class="bi bi-check-circle-fill text-success fs-5 position-absolute top-0 end-0 mt-3 me-3"></i>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Sticky Bottom Bar -->
    <footer class="fixed-bottom-bar">
        <button id="btnContinueWorkspace" class="btn btn-caspa-teal px-4 py-2 fs-6">
            Continue →
        </button>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/auth.js"></script>
</body>
</html>
