<?php
/**
 * CASPA School OS – pages/principal/dashboard.php
 *
 * Role check, then the shared school-wide overview (pages/shared/_overview_dashboard.php).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_role('principal');

$pageRole = 'principal';

require_once ROOT . '/pages/shared/_overview_dashboard.php';
