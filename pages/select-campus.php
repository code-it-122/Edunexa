<?php
// pages/select-campus.php - Matching Figma 'select campus' Screen
require_once __DIR__ . '/../config/session.php';
requireAuth();

$pdo = getDbConnection();
$campuses = $pdo->query("SELECT * FROM campuses WHERE status = 'active'")->fetchAll();
$user = getCurrentUser();
$activeCampusId = $user['active_campus']['id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Campus - EduNEXA</title>
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
            ‹ YOUR SCHOOL NETWORK
        </div>
        <h1 class="selection-head">Select your campus</h1>
        <p class="selection-sub">Choose the campus you want to work in today.</p>

        <!-- Search Bar -->
        <div class="mb-4" style="max-width: 480px; position: relative;">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" id="campusSearchInput" class="form-control form-control-custom ps-5" placeholder="Search by campus or location">
        </div>

        <!-- Campus Grid -->
        <div class="grid-cards">
            <?php foreach ($campuses as $campus): 
                $isSelected = ($campus['id'] == $activeCampusId);
            ?>
                <div class="selection-item-card campus-card-select <?= $isSelected ? 'active-selected' : '' ?>" data-id="<?= $campus['id'] ?>">
                    <div class="card-icon-box">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold fs-6 card-title-text" style="color: var(--caspa-text);"><?= htmlspecialchars($campus['name']) ?></div>
                        <div class="small text-muted card-desc-text">📍 <?= htmlspecialchars($campus['location']) ?></div>
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
        <button id="btnContinueCampus" class="btn btn-caspa-teal px-4 py-2 fs-6">
            Continue →
        </button>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/auth.js"></script>
</body>
</html>
