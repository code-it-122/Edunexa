<?php
// pages/login.php - Matching Figma 'workplace' Screen
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in to EduNEXA - CASPA School OS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/caspa-theme.css">
</head>
<body>

<div class="auth-split-wrapper">
    <!-- Left Banner (Figma workplace) -->
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

    <!-- Right Form Pane -->
    <div class="auth-right-pane">
        <div class="auth-box-card">
            <h2 class="auth-box-title">Log in to EduNEXA</h2>
            <p class="auth-box-subtitle">Enter your details to access your workspace.</p>

            <div id="authAlert" class="alert d-none py-2 px-3 small mb-3"></div>

            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label form-label-custom">Email Or ID</label>
                    <input type="email" class="form-control form-control-custom" id="loginEmail" placeholder="name@domain.edu" value="admin@edunexa.edu" required>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label form-label-custom mb-0">Password</label>
                        <a href="index.php?page=forgot-password" style="font-size: 12px; font-weight: 600; color: var(--caspa-teal);">Forgot password?</a>
                    </div>
                    <input type="password" class="form-control form-control-custom" id="loginPassword" placeholder="••••••••" value="password123" required>
                </div>

                <button type="submit" class="btn btn-caspa-teal w-100 mt-2 py-2 fs-6">
                    Log In
                </button>
            </form>

            <!-- Quick Demo Accounts for Seamless Review -->
            <div class="mt-4 pt-3 border-top">
                <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                    ⚡ Quick Demo Accounts (Click to load):
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-demo-account" data-email="principal@edunexa.edu">🎓 Principal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-demo-account" data-email="admin@edunexa.edu">⚙️ Admin</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-demo-account" data-email="teacher@edunexa.edu">👩‍🏫 Teacher</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-demo-account" data-email="student@edunexa.edu">👨‍🎓 Student</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-demo-account" data-email="parent@edunexa.edu">👨‍👩‍👧 Parent</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-demo-account" data-email="safety@edunexa.edu">🛡️ Safety</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/auth.js"></script>
</body>
</html>
