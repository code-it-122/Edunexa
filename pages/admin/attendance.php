<?php
/**
 * CASPA School OS – pages/admin/attendance.php
 *
 * Role check, then the shared school-wide attendance overview.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_role('admin');

$pageTitle = 'Attendance Overview';
require_once ROOT . '/pages/shared/attendance_overview.php';
