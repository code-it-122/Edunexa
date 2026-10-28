<?php
/**
 * CASPA School OS – GET /api/timetable/periods.php
 * Returns all periods. No role restriction (any logged-in user).
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();

$rows = db()->query('SELECT id, period_number, label, start_time, end_time, is_break FROM periods ORDER BY period_number')->fetchAll();
json_ok($rows);
