<?php
/**
 * CASPA School OS – api/auth/login.php
 *
 * POST  email, password
 * Returns { ok: true, data: { redirect: "/pages/teacher/dashboard.php" } }
 *         { ok: false, error: "..." }
 *
 * Throttle: 5 failed attempts per session → 30-second lock.
 */

declare(strict_types=1);

header('Content-Type: application/json');

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/response.php';

session_boot();

// ── Only accept POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Method not allowed.', 405);
}

// ── Throttle check ──────────────────────────────────────────────────────────
$attempts  = $_SESSION['login_attempts']  ?? 0;
$lockedAt  = $_SESSION['login_locked_at'] ?? 0;
$lockSecs  = 30;
$maxTries  = 5;

if ($attempts >= $maxTries) {
    $elapsed = time() - $lockedAt;
    if ($elapsed < $lockSecs) {
        $wait = $lockSecs - $elapsed;
        json_fail("Too many failed attempts. Please wait {$wait} second(s).", 429);
    }
    // Lock expired – reset counters
    $_SESSION['login_attempts']  = 0;
    $_SESSION['login_locked_at'] = 0;
    $attempts = 0;
}

// ── Validate input ──────────────────────────────────────────────────────────
$fields = json_validate([
    'email'    => 'required|email',
    'password' => 'required',
]);

$email    = strtolower($fields['email']);
$password = $fields['password'];

// ── Look up user ────────────────────────────────────────────────────────────
$pdo = db();
$st  = $pdo->prepare('SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1');
$st->execute([':email' => $email]);
$user = $st->fetch();

// Generic message – same for wrong email OR wrong password (prevents enumeration)
$genericError = 'Invalid email or password.';

if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['login_attempts']  = $attempts + 1;
    if (($_SESSION['login_attempts']) >= $maxTries) {
        $_SESSION['login_locked_at'] = time();
    }
    json_fail($genericError, 401);
}

if (!(bool) $user['is_active']) {
    json_fail('Your account has been deactivated. Please contact your administrator.', 403);
}

// ── Success – reset throttle, write session ─────────────────────────────────
$_SESSION['login_attempts']  = 0;
$_SESSION['login_locked_at'] = 0;

login_user($user);   // writes session + session_regenerate_id(true)

// ── Role → redirect URL ─────────────────────────────────────────────────────
$redirectMap = [
    'principal' => '/pages/principal/dashboard.php',
    'admin'     => '/pages/admin/dashboard.php',
    'teacher'   => '/pages/teacher/dashboard.php',
    'student'   => '/pages/student/dashboard.php',
    'parent'    => '/pages/parent/dashboard.php',
];

$redirect = APP_URL . ($redirectMap[$user['role']] ?? '/');

json_ok(['redirect' => $redirect]);
