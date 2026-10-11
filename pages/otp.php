<?php
// pages/otp.php - Matching Figma 'otp screen'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-factor Authentication - EduNEXA</title>
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
            <h2 class="auth-box-title">Two-factor Authentication</h2>
            <p class="auth-box-subtitle">Enter the 6-digit code sent to your registered mobile.</p>

            <div id="otpAlert" class="alert d-none py-2 px-3 small mb-3"></div>

            <div class="alert alert-info py-2 px-3 small mb-3" style="background-color: var(--caspa-teal-light); border-color: var(--caspa-teal); color: var(--caspa-teal);">
                <i class="bi bi-info-circle me-1"></i> Demo OTP Code: <strong>123456</strong> (Pre-filled below)
            </div>

            <form id="otpForm">
                <div class="otp-container">
                    <input type="text" class="otp-field" maxlength="1" value="1" required>
                    <input type="text" class="otp-field" maxlength="1" value="2" required>
                    <input type="text" class="otp-field" maxlength="1" value="3" required>
                    <input type="text" class="otp-field" maxlength="1" value="4" required>
                    <input type="text" class="otp-field" maxlength="1" value="5" required>
                    <input type="text" class="otp-field" maxlength="1" value="6" required>
                </div>

                <div class="d-flex justify-content-end mb-3" id="resendBlock" style="font-size: 12px; color: var(--caspa-muted);">
                    <span>Resend code in <strong id="otpCountdown">00:29</strong></span>
                </div>

                <button type="submit" class="btn btn-caspa-teal w-100 py-2 fs-6">
                    Verify
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
