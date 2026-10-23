<?php
/**
 * CASPA School OS – pages/principal/announcements.php
 *
 * Announcements Management for Principal (delegates to shared announcements page).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';

session_boot();
require_login();
require_role('principal');

require_once ROOT . '/pages/admin/announcements.php';
