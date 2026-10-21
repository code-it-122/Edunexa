<?php
/**
 * CASPA School OS – pages/principal/dashboard.php
 */

declare(strict_types=1);
define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
session_boot();

require_role('principal');

$user      = current_user();
$pageTitle = 'Dashboard';
$pageRole  = 'principal';

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header">
    <h1>Principal Dashboard</h1>
    <p class="caspa-breadcrumb">Welcome back, <?= htmlspecialchars($user['name'], ENT_QUOTES) ?> &mdash; here's your school at a glance.</p>
</div>

<div class="alert alert-info d-flex align-items-center gap-2 py-2" role="status">
    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
    <span>This is the <strong>Principal</strong> dashboard placeholder. Module cards will be added here.</span>
</div>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
