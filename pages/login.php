<?php
/**
 * CASPA School OS – Application entry point / login page
 *
 * • Authenticated users → redirected to their role dashboard immediately.
 * • Everyone else → sees the login form.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

require_once ROOT . '/includes/auth.php';

session_boot();

// ── Already logged in? Redirect. ─────────────────────────────────────────────
$user = current_user();
if ($user) {
    $map = [
        'principal' => '/pages/principal/dashboard.php',
        'admin'     => '/pages/admin/dashboard.php',
        'teacher'   => '/pages/teacher/dashboard.php',
        'student'   => '/pages/student/dashboard.php',
        'parent'    => '/pages/parent/dashboard.php',
    ];
    header('Location: ' . APP_URL . ($map[$user['role']] ?? '/'));
    exit;
}

// ── Guest → render login page ────────────────────────────────────────────────
$errorMsg = '';
if (!empty($_GET['error'])) {
    $errorMsg = htmlspecialchars($_GET['error'], ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – CASPA School OS</title>

    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CASPA App CSS (design tokens) -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">

    <style>
        /* ── Login-only layout ── */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-navy) 0%, var(--color-teal) 100%);
            padding: 1.5rem;
        }

        .login-shell {
            width: 100%;
            max-width: 440px;
        }

        /* Brand strip above card */
        .login-brand {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #fff;
        }
        .login-brand-icon { font-size: 2.5rem; }
        .login-brand-name {
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-top: 0.25rem;
        }
        .login-brand-tagline {
            font-size: 0.875rem;
            opacity: 0.75;
        }

        /* Card */
        .login-card {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2.25rem 2rem;
        }

        .login-card h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-navy);
            margin-bottom: 0.25rem;
        }
        .login-card .login-subtitle {
            font-size: 0.875rem;
            color: var(--color-text-muted);
            margin-bottom: 1.75rem;
        }

        /* Inputs */
        .login-card .caspa-input {
            padding-right: 2.75rem; /* room for show/hide icon */
        }

        /* Password wrapper */
        .pw-wrapper {
            position: relative;
        }
        .pw-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            color: var(--color-text-muted);
            cursor: pointer;
            font-size: 1.05rem;
            line-height: 1;
        }
        .pw-toggle:focus-visible {
            outline: 2px solid var(--color-teal);
            border-radius: 3px;
        }

        /* Submit button */
        #btnLogin {
            width: 100%;
            padding: 0.65rem 1rem;
            font-size: 0.9375rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        /* Error banner */
        .login-error {
            font-size: 0.875rem;
            border-left: 4px solid var(--color-danger);
            border-radius: var(--radius-sm);
        }

        /* Throttle countdown */
        .login-countdown {
            font-size: 0.875rem;
            border-left: 4px solid var(--color-warning);
            border-radius: var(--radius-sm);
        }

        /* Invalid-field highlight */
        .caspa-input.is-invalid {
            border-color: var(--color-danger);
            box-shadow: 0 0 0 3px rgba(220,38,38,.15);
        }
        .field-error {
            font-size: 0.8125rem;
            color: var(--color-danger);
            margin-top: 0.25rem;
        }

        /* Footer link */
        .login-footer-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.875rem;
            color: var(--color-text-muted);
        }
        .login-footer-link a { color: var(--color-teal); font-weight: 500; }
    </style>
</head>
<body>

<div class="login-shell">

    <!-- Brand -->
    <div class="login-brand">
        <div class="login-brand-icon">🎓</div>
        <div class="login-brand-name">CASPA School OS</div>
        <div class="login-brand-tagline">Role-based school management platform</div>
    </div>

    <!-- Card -->
    <div class="login-card">
        <h1>Welcome back</h1>
        <p class="login-subtitle">Sign in to your workspace</p>

        <!-- Server-side redirect error (e.g. session_expired) -->
        <?php if ($errorMsg): ?>
        <div class="alert alert-danger login-error py-2 mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= $errorMsg ?>
        </div>
        <?php endif; ?>

        <!-- AJAX error banner (hidden until needed) -->
        <div id="loginError" class="alert alert-danger login-error py-2 mb-3 d-none" role="alert" aria-live="assertive">
            <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i>
            <span id="loginErrorMsg"></span>
        </div>

        <!-- Throttle countdown banner -->
        <div id="loginCountdown" class="alert alert-warning login-countdown py-2 mb-3 d-none" role="status" aria-live="polite">
            <i class="bi bi-clock me-2" aria-hidden="true"></i>
            Too many failed attempts. Try again in <strong id="cdSeconds"></strong> second(s).
        </div>

        <form id="loginForm" novalidate>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="caspa-form-label">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="caspa-input"
                    placeholder="you@school.edu"
                    autocomplete="email"
                    required
                >
                <div class="field-error d-none" id="emailError"></div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="caspa-form-label">Password</label>
                <div class="pw-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="caspa-input"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        type="button"
                        id="pwToggle"
                        class="pw-toggle"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        <i class="bi bi-eye" id="pwToggleIcon" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="field-error d-none" id="passwordError"></div>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                id="btnLogin"
                class="btn btn-caspa-primary mt-2"
            >
                Sign In
            </button>

        </form>

        <!-- Forgot password (placeholder) -->
        <div class="login-footer-link">
            <a href="#">Forgot password?</a>
        </div>
    </div><!-- /.login-card -->

</div><!-- /.login-shell -->

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>

<!-- Bootstrap JS (for any future BS components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

<script>
/* global $, APP_URL */
const APP_URL = '<?= APP_URL ?>';

$(function () {

    /* ── Show/hide password ───────────────────────────────────────── */
    $('#pwToggle').on('click', function () {
        const $input  = $('#password');
        const $icon   = $('#pwToggleIcon');
        const showing = $input.attr('type') === 'text';

        $input.attr('type', showing ? 'password' : 'text');
        $icon.toggleClass('bi-eye', showing).toggleClass('bi-eye-slash', !showing);
        $(this).attr('aria-label', showing ? 'Show password' : 'Hide password')
               .attr('aria-pressed', String(!showing));
    });

    /* ── Inline client-side validation helpers ────────────────────── */
    function setFieldError($input, errorId, msg) {
        $input.addClass('is-invalid');
        $('#' + errorId).text(msg).removeClass('d-none');
    }
    function clearFieldError($input, errorId) {
        $input.removeClass('is-invalid');
        $('#' + errorId).addClass('d-none');
    }

    function validateForm() {
        let valid = true;
        const $email    = $('#email');
        const $password = $('#password');

        // Email
        const emailVal = $email.val().trim();
        if (!emailVal) {
            setFieldError($email, 'emailError', 'Email address is required.');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
            setFieldError($email, 'emailError', 'Please enter a valid email address.');
            valid = false;
        } else {
            clearFieldError($email, 'emailError');
        }

        // Password
        const pwVal = $password.val();
        if (!pwVal) {
            setFieldError($password, 'passwordError', 'Password is required.');
            valid = false;
        } else {
            clearFieldError($password, 'passwordError');
        }

        return valid;
    }

    // Clear inline errors when user starts typing
    $('#email').on('input', function () { clearFieldError($(this), 'emailError'); });
    $('#password').on('input', function () { clearFieldError($(this), 'passwordError'); });

    /* ── Throttle countdown timer ─────────────────────────────────── */
    let countdownTimer = null;

    function startCountdown(seconds) {
        const $cd = $('#loginCountdown');
        const $s  = $('#cdSeconds');
        const $btn = $('#btnLogin');

        $cd.removeClass('d-none');
        $btn.prop('disabled', true);

        let remaining = seconds;
        $s.text(remaining);

        countdownTimer = setInterval(function () {
            remaining--;
            $s.text(remaining);
            if (remaining <= 0) {
                clearInterval(countdownTimer);
                $cd.addClass('d-none');
                $btn.prop('disabled', false);
            }
        }, 1000);
    }

    /* ── Login form submission ────────────────────────────────────── */
    $('#loginForm').on('submit', function (e) {
        e.preventDefault();

        // Clear any existing error banner
        $('#loginError').addClass('d-none');

        if (!validateForm()) return;

        const $btn = $('#btnLogin');

        // Loading state
        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in…');

        $.ajax({
            url   : APP_URL + '/api/auth/login.php',
            method: 'POST',
            data  : {
                email   : $('#email').val().trim(),
                password: $('#password').val(),
            },
            dataType: 'json',
        })
        .done(function (res) {
            if (res && res.ok) {
                // Keep button in loading/disabled state while navigating
                $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Redirecting…');
                window.location.href = res.data.redirect;
            } else {
                showLoginError(res.error || 'An unexpected error occurred.');
            }
        })
        .fail(function (jqXHR) {
            let msg = 'Network error. Please try again.';
            let waitSeconds = 0;

            try {
                const json = JSON.parse(jqXHR.responseText);
                if (json && json.error) msg = json.error;

                // Extract wait time from throttle message "wait N second(s)"
                const match = msg.match(/wait (\d+) second/);
                if (match) waitSeconds = parseInt(match[1], 10);
            } catch (_) { /* ignore */ }

            if (waitSeconds > 0) {
                $('#loginError').addClass('d-none');
                startCountdown(waitSeconds);
            } else {
                showLoginError(msg);
            }

            resetButton();
        });
    });

    function showLoginError(msg) {
        $('#loginErrorMsg').text(msg);
        $('#loginError').removeClass('d-none');
        resetButton();
    }

    function resetButton() {
        if (!countdownTimer || $('#loginCountdown').hasClass('d-none')) {
            $('#btnLogin').prop('disabled', false);
        }
        $('#btnLogin').html('Sign In');
    }

});
</script>

</body>
</html>
