<?php
/**
 * CASPA School OS – Application entry point
 *
 * Authenticated  → redirect to role dashboard.
 * Unauthenticated → redirect to login page.
 */

declare(strict_types=1);

define('ROOT', __DIR__);

require_once ROOT . '/includes/auth.php';

session_boot();

$user = current_user();

if ($user) {
    $map = [
        'principal' => '/pages/principal/dashboard.php',
        'admin'     => '/pages/admin/dashboard.php',
        'teacher'   => '/pages/teacher/dashboard.php',
        'student'   => '/pages/student/dashboard.php',
        'parent'    => '/pages/parent/dashboard.php',
    ];
    header('Location: ' . APP_URL . ($map[$user['role']] ?? '/pages/login.php'));
} else {
    header('Location: ' . APP_URL . '/pages/login.php');
}

exit;
