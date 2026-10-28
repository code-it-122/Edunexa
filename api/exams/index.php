<?php
/**
 * CASPA School OS – /api/exams/index.php
 *
 * GET              – list exams (role-scoped)
 * POST {action:"create", name, term, exam_type, academic_year_id} – admin/principal only
 * POST {action:"publish", exam_id, is_published} – admin/principal only
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();

$user   = current_user();
$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* ── GET ─────────────────────────────────────────────────────────────────── */
if ($method === 'GET') {
    $onlyPublished = in_array($user['role'], ['student', 'parent'], true);
    $wherePub = $onlyPublished ? 'WHERE e.is_published = 1' : '';
    $rows = $pdo->query("
        SELECT e.id, e.name, e.term, e.exam_type, e.is_published,
               e.academic_year_id, ay.label AS academic_year, e.created_at,
               u.name AS created_by_name
        FROM   exams e
        JOIN   academic_years ay ON ay.id = e.academic_year_id
        JOIN   users u ON u.id = e.created_by
        {$wherePub}
        ORDER  BY e.created_at DESC
    ")->fetchAll();
    json_ok($rows);
}

/* ── POST ────────────────────────────────────────────────────────────────── */
require_role(['admin', 'principal']);

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

if ($action === 'create') {
    $name   = trim($body['name'] ?? '');
    $term   = trim($body['term'] ?? 'Term 1');
    $type   = trim($body['exam_type'] ?? 'midterm');
    $ayId   = (int) ($body['academic_year_id'] ?? 0);
    $validTypes = ['midterm', 'final', 'unit', 'other'];

    if (!$name)                         { json_fail('Exam name is required.',            400); }
    if (!$ayId)                         { json_fail('academic_year_id is required.',     400); }
    if (!in_array($type, $validTypes, true)) { json_fail('Invalid exam_type.',           400); }

    $st = $pdo->prepare('INSERT INTO exams (academic_year_id, name, term, exam_type, created_by) VALUES (?,?,?,?,?)');
    $st->execute([$ayId, $name, $term, $type, $user['id']]);
    json_ok(['id' => (int)$pdo->lastInsertId()], 201);
}

if ($action === 'publish') {
    $examId = (int) ($body['exam_id'] ?? 0);
    $pub    = (int) (bool) ($body['is_published'] ?? false);
    if (!$examId) { json_fail('exam_id required.', 400); }
    $pdo->prepare('UPDATE exams SET is_published = ? WHERE id = ?')->execute([$pub, $examId]);
    json_ok(['exam_id' => $examId, 'is_published' => $pub]);
}

json_fail('Unknown action.', 400);
