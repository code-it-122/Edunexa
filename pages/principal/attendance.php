<?php
/**
 * CASPA School OS – pages/principal/attendance.php
 *
 * Attendance Overview for Principal (delegates to shared attendance page).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';

session_boot();
require_login();
require_role('principal');

require_once ROOT . '/pages/teacher/attendance.php';
