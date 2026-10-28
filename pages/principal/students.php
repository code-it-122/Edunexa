<?php
/**
 * CASPA School OS – pages/principal/students.php
 *
 * The principal uses the same read-only student directory as the admin page.
 * pages/admin/students.php enforces roles itself (admin, principal, teacher).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/pages/admin/students.php';
