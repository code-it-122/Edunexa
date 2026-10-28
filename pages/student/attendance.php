<?php
/**
 * CASPA School OS – pages/student/attendance.php
 *
 * Role check, then the shared student/parent attendance page.
 * The API limits the data: student → self only, parent → linked children only.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_role('student');

$pageRole = 'student';

require_once ROOT . '/pages/shared/_family_attendance.php';
