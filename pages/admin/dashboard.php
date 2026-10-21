<?php
/**
 * CASPA School OS – pages/admin/dashboard.php
 */

declare(strict_types=1);
define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
session_boot();

require_role('admin');

$user      = current_user();
$pageTitle = 'Dashboard';
$pageRole  = 'admin';

require_once ROOT . '/includes/layout_header.php';
?>

<div class="caspa-page-header">
    <h1>Admin Dashboard</h1>
    <p class="caspa-breadcrumb">Welcome back, <?= htmlspecialchars($user['name'], ENT_QUOTES) ?> &mdash; manage the school here.</p>
</div>

<div class="alert alert-info d-flex align-items-center gap-2 py-2" role="status">
    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
    <span>This is the <strong>Admin</strong> dashboard placeholder. Module cards will be added here.</span>
</div>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
