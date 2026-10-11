<?php
// api/auth.php - Authentication API Endpoints
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';

$pdo = getDbConnection();
$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    // 1. LOGIN
    if ($action === 'login') {
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($email) || empty($password)) {
            jsonResponse(['error' => 'Please enter both Email/ID and Password.'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([strtolower($email)]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['error' => 'Invalid email or password.'], 401);
        }

        // Two-factor authentication OTP generation
        $demoOtp = '123456';
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $pdo->prepare("DELETE FROM otps WHERE email = ? AND purpose = '2fa'")->execute([$user['email']]);
        $pdo->prepare("INSERT INTO otps (email, code, purpose, expires_at) VALUES (?, ?, '2fa', ?)")
            ->execute([$user['email'], $demoOtp, $expires]);

        $_SESSION['temp_2fa_user_id'] = $user['id'];
        $_SESSION['temp_2fa_email'] = $user['email'];

        jsonResponse([
            'success' => true,
            'requireOtp' => true,
            'email' => $user['email'],
            'demoOtp' => $demoOtp,
            'message' => 'Credentials verified. 2FA OTP code generated.'
        ]);
    }

    // 2. VERIFY OTP
    if ($action === 'verify_otp') {
        $otp = trim($input['otp'] ?? '');
        $userId = $_SESSION['temp_2fa_user_id'] ?? null;
        $email = $_SESSION['temp_2fa_email'] ?? '';

        if (!$userId || empty($otp)) {
            jsonResponse(['error' => 'Session expired or OTP missing. Please log in again.'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM otps WHERE email = ? AND code = ? AND purpose = '2fa' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$email, $otp]);
        $record = $stmt->fetch();

        if (!$record) {
            jsonResponse(['error' => 'Invalid 6-digit verification code.'], 400);
        }

        // Clean up OTP & establish full authenticated session
        $pdo->prepare("DELETE FROM otps WHERE id = ?")->execute([$record['id']]);

        $_SESSION['user_id'] = $userId;
        unset($_SESSION['temp_2fa_user_id']);
        unset($_SESSION['temp_2fa_email']);

        // Determine primary role
        $urStmt = $pdo->prepare("
            SELECT r.key FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.user_id = ? ORDER BY ur.is_primary DESC LIMIT 1
        ");
        $urStmt->execute([$userId]);
        $role = $urStmt->fetch();
        $_SESSION['active_role_key'] = $role['key'] ?? 'principal';
        $_SESSION['active_campus_id'] = 1;

        jsonResponse([
            'success' => true,
            'redirect' => 'index.php?page=select-campus'
        ]);
    }

    // 3. FORGOT PASSWORD
    if ($action === 'forgot_password') {
        $email = trim($input['email'] ?? '');
        if (empty($email)) {
            jsonResponse(['error' => 'Please enter your registered email address.'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([strtolower($email)]);
        $user = $stmt->fetch();

        $demoOtp = '123456';
        if ($user) {
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $pdo->prepare("DELETE FROM otps WHERE email = ? AND purpose = 'password_reset'")->execute([$user['email']]);
            $pdo->prepare("INSERT INTO otps (email, code, purpose, expires_at) VALUES (?, ?, 'password_reset', ?)")
                ->execute([$user['email'], $demoOtp, $expires]);
        }

        $_SESSION['reset_email'] = $email;

        jsonResponse([
            'success' => true,
            'email' => $email,
            'demoOtp' => $demoOtp,
            'message' => 'Reset link and 6-digit code sent.'
        ]);
    }

    // 4. RESET PASSWORD
    if ($action === 'reset_password') {
        $email = trim($input['email'] ?? ($_SESSION['reset_email'] ?? ''));
        $password = trim($input['password'] ?? '');
        $confirmPassword = trim($input['confirm_password'] ?? '');

        if (empty($email) || empty($password) || empty($confirmPassword)) {
            jsonResponse(['error' => 'Please fill in all fields.'], 400);
        }

        if ($password !== $confirmPassword) {
            jsonResponse(['error' => 'Passwords do not match.'], 400);
        }

        if (strlen($password) < 6) {
            jsonResponse(['error' => 'Password must be at least 6 characters.'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([strtolower($email)]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(['error' => 'Account not found.'], 404);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);

        unset($_SESSION['reset_email']);

        jsonResponse([
            'success' => true,
            'message' => 'Password has been reset successfully! Redirecting to login...'
        ]);
    }

    // 5. LOGOUT
    if ($action === 'logout') {
        session_destroy();
        jsonResponse(['success' => true, 'redirect' => 'index.php?page=login']);
    }
}

jsonResponse(['error' => 'Invalid endpoint or request method.'], 400);
