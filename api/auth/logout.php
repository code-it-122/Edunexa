<?php
/**
 * CASPA School OS – api/auth/logout.php
 *
 * GET or POST – destroys session and redirects to index.php.
 * Safe to call even when not logged in.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
logout_user();

header('Location: ' . APP_URL . '/index.php');
exit;
