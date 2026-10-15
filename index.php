<?php
/**
 * CASPA School OS – Application entry point
 *
 * Redirects authenticated users to their role dashboard.
 * Unauthenticated users see the login form (to be built).
 */

declare(strict_types=1);

define('ROOT', __DIR__);

require_once ROOT . '/includes/auth.php';

session_boot();

$user = current_user();

if ($user) {
    // Redirect to role-specific dashboard
    $role = $user['role'];
    header('Location: ' . APP_URL . '/pages/' . $role . '/dashboard.php');
    exit;
}

// Show a minimal placeholder until the login page is built
$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CASPA School OS – Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
    <style>
        body { min-height: 100vh; display:flex; align-items:center; justify-content:center; background: linear-gradient(135deg, #123B5D 0%, #176B87 100%); }
        .login-card { max-width:420px; width:100%; background:#fff; border-radius:16px; padding:2.5rem; box-shadow: 0 16px 48px rgba(0,0,0,.22); }
        .login-brand { font-size:1.5rem; font-weight:700; color:#123B5D; margin-bottom:.25rem; }
        .login-sub   { color:#64748B; font-size:.875rem; margin-bottom:2rem; }
        .placeholder-note { background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:8px; padding:1rem; font-size:.8125rem; color:#64748B; text-align:center; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center">
            <div style="font-size:2.5rem;">🎓</div>
            <div class="login-brand">CASPA School OS</div>
            <div class="login-sub">Role-based school management</div>
        </div>

        <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger py-2 small">
            <?= htmlspecialchars($_GET['error'], ENT_QUOTES) ?>
        </div>
        <?php endif; ?>

        <div class="placeholder-note">
            <strong>🚧 Login page coming soon.</strong><br>
            Skeleton is set up. The auth feature will be built next.
        </div>
    </div>
</body>
</html>
