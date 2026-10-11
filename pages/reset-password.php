<?php
// pages/reset-password.php - Matching Figma 'Reset password' Screen
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - EduNEXA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/caspa-theme.css">
</head>
<body>

<div class="auth-split-wrapper">
    <!-- Left Banner -->
    <div class="auth-left-pane">
        <div class="auth-brand-logo">
            <div class="brand-badge-box">EN</div>
            <span>EduNexa</span>
        </div>

        <div class="auth-hero-copy">
            <span class="auth-hero-tagline">ONE SCHOOL. ONE PLATFORM.</span>
            <h1 class="auth-hero-heading">Complete visibility. Controlled access. Intelligent automation.</h1>
            <p class="auth-hero-text">
                A unified school operating system for Pre-Primary to Grade 12 — academics, operations, safety and AI in one role-based platform.
            </p>
        </div>

        <div style="font-size: 12px; color: #8AD4E8;">
            © EduNexa • CASPA AI School OS
        </div>
    </div>

    <!-- Right Pane -->
    <div class="auth-right-pane">
        <div class="auth-box-card">
            <h2 class="auth-box-title">Reset Password</h2>
            <p class="auth-box-subtitle">Set your new password to regain access to your workspace.</p>

            <div id="resetAlert" class="alert d-none py-2 px-3 small mb-3"></div>

            <form id="resetPasswordForm">
                <div class="mb-3">
                    <label class="form-label form-label-custom">Enter Password</label>
                    <input type="password" class="form-control form-control-custom" id="newPassword" placeholder="••••••••" required>
                </div>

                <div class="mb-3">
                    <label class="form-label form-label-custom">Confirm Password</label>
                    <input type="password" class="form-control form-control-custom" id="confirmPassword" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-caspa-teal w-100 mt-2 py-2 fs-6">
                    Log In
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/auth.js"></script>
</body>
</html>
