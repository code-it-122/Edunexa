<?php
/**
 * CASPA School OS – /api/exams/grade_bands.php
 *
 * GET  ?academic_year_id=  – list grade bands
 * POST {action:"save", academic_year_id, bands:[{min_pct,max_pct,grade_letter,description}]}
 *   admin/principal only – replaces bands for that year
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $ayId = (int) ($_GET['academic_year_id'] ?? 0);
    if (!$ayId) {
        $ayId = (int) $pdo->query('SELECT id FROM academic_years WHERE is_current=1 LIMIT 1')->fetchColumn();
    }
    $rows = $pdo->prepare('SELECT * FROM grade_bands WHERE academic_year_id=? ORDER BY min_pct DESC');
    $rows->execute([$ayId]);
    json_ok($rows->fetchAll());
}

require_role(['admin', 'principal']);
$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$ayId  = (int) ($body['academic_year_id'] ?? 0);
$bands = $body['bands'] ?? [];

if (!$ayId)           { json_fail('academic_year_id required.', 400); }
if (!is_array($bands)) { json_fail('bands must be an array.', 400); }

$pdo->beginTransaction();
try {
    $pdo->prepare('DELETE FROM grade_bands WHERE academic_year_id=?')->execute([$ayId]);
    $ins = $pdo->prepare('INSERT INTO grade_bands (academic_year_id,min_pct,max_pct,grade_letter,description) VALUES (?,?,?,?,?)');
    foreach ($bands as $b) {
        $ins->execute([$ayId, (float)($b['min_pct']??0), (float)($b['max_pct']??0), trim($b['grade_letter']??''), trim($b['description']??'')]);
    }
    $pdo->commit();
} catch (\Exception $e) {
    $pdo->rollBack();
    json_fail('Save failed: '.$e->getMessage(), 500);
}
json_ok(['saved' => count($bands)]);
