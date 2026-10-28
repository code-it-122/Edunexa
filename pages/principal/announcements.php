<?php
/**
 * CASPA School OS – pages/principal/announcements.php
 *
 * Role check, then the shared page (announcements.php).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_role('principal');

$pageTitle = 'Announcements';
require_once ROOT . '/pages/shared/announcements.php';
