<?php
// index.php - Main Application Router & Entry Point
require_once __DIR__ . '/config/session.php';

$page = $_GET['page'] ?? '';

// Routing map
$publicPages = ['login', 'forgot-password', 'otp', 'reset-password'];

if (empty($page)) {
    if (isLoggedIn()) {
        $page = 'dashboard';
    } else {
        $page = 'login';
    }
}

// Redirect logged-in users away from login/auth pages to dashboard
if (isLoggedIn() && in_array($page, ['login', 'forgot-password', 'reset-password'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

// Route to requested page
switch ($page) {
    case 'login':
        require __DIR__ . '/pages/login.php';
        break;

    case 'forgot-password':
        require __DIR__ . '/pages/forgot-password.php';
        break;

    case 'otp':
        require __DIR__ . '/pages/otp.php';
        break;

    case 'reset-password':
        require __DIR__ . '/pages/reset-password.php';
        break;

    case 'select-campus':
        require __DIR__ . '/pages/select-campus.php';
        break;

    case 'choose-workspace':
        require __DIR__ . '/pages/choose-workspace.php';
        break;

    case 'dashboard':
        require __DIR__ . '/pages/dashboard.php';
        break;

    case 'attendance-mark':
        require __DIR__ . '/pages/attendance-mark.php';
        break;

    default:
        if (isLoggedIn()) {
            require __DIR__ . '/pages/dashboard.php';
        } else {
            require __DIR__ . '/pages/login.php';
        }
        break;
}
