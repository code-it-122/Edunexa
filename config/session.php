<?php
// config/session.php - Session Management & Auth Helper Functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /Edunexa/index.php?page=login');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, email, full_name, phone, default_campus_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    // Attach active campus & active role from session or database defaults
    $campusId = $_SESSION['active_campus_id'] ?? $user['default_campus_id'] ?? 1;
    $campusStmt = $pdo->prepare("SELECT * FROM campuses WHERE id = ?");
    $campusStmt->execute([$campusId]);
    $campus = $campusStmt->fetch() ?: ['id' => 1, 'name' => 'Main Campus', 'code' => 'CAMP-01', 'location' => 'Green Park, City Center'];

    $roleKey = $_SESSION['active_role_key'] ?? 'principal';
    $roleStmt = $pdo->prepare("SELECT * FROM roles WHERE key = ?");
    $roleStmt->execute([$roleKey]);
    $role = $roleStmt->fetch() ?: ['key' => 'principal', 'title' => 'Principal', 'category' => 'Leadership'];

    $user['active_campus'] = $campus;
    $user['active_role'] = $role;

    return $user;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

