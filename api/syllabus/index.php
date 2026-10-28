<?php
/**
 * CASPA School OS – /api/syllabus/index.php
 *
 * GET  ?class_subject_id=  – list units for a class_subject
 * POST {action:"create"|"update"|"delete", ...}
 *   create: {class_subject_id, unit_number, title, description}
 *   update: {id, title, description}
 *   delete: {id}
 *
 * Scope: teacher (own class_subjects), admin, principal
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
    $csId = (int) ($_GET['class_subject_id'] ?? 0);
    if (!$csId) { json_fail('class_subject_id required.', 400); }
    $rows = $pdo->prepare('SELECT * FROM syllabus WHERE class_subject_id=? ORDER BY unit_number');
    $rows->execute([$csId]);
    json_ok($rows->fetchAll());
}

/* ── POST ────────────────────────────────────────────────────────────────── */
require_role(['admin', 'principal', 'teacher']);

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

// Helper: verify teacher owns the class_subject
$assertOwner = function(int $csId) use ($user, $pdo): void {
    if (in_array($user['role'], ['admin', 'principal'], true)) return;
    $chk = $pdo->prepare('SELECT teacher_id FROM class_subjects WHERE id=?');
    $chk->execute([$csId]);
    $row = $chk->fetch();
    if (!$row || (int)$row['teacher_id'] !== $user['id']) { json_fail('Forbidden.', 403); }
};

if ($action === 'create') {
    $csId   = (int) ($body['class_subject_id'] ?? 0);
    $unit   = (int) ($body['unit_number']      ?? 0);
    $title  = trim($body['title'] ?? '');
    $desc   = trim($body['description'] ?? '');
    if (!$csId || !$unit || !$title) { json_fail('class_subject_id, unit_number and title required.', 400); }
    $assertOwner($csId);
    try {
        $st = $pdo->prepare('INSERT INTO syllabus (class_subject_id,unit_number,title,description,created_by) VALUES (?,?,?,?,?)');
        $st->execute([$csId, $unit, $title, $desc ?: null, $user['id']]);
    } catch (\PDOException) { json_fail('Unit number already exists for this subject.', 409); }
    json_ok(['id' => (int)$pdo->lastInsertId()], 201);
}

if ($action === 'update') {
    $id    = (int) ($body['id']    ?? 0);
    $title = trim($body['title'] ?? '');
    $desc  = trim($body['description'] ?? '');
    if (!$id || !$title) { json_fail('id and title required.', 400); }
    // Load to check owner
    $row = $pdo->prepare('SELECT class_subject_id FROM syllabus WHERE id=?');
    $row->execute([$id]); $row = $row->fetch();
    if (!$row) { json_fail('Not found.', 404); }
    $assertOwner((int)$row['class_subject_id']);
    $pdo->prepare('UPDATE syllabus SET title=?,description=? WHERE id=?')->execute([$title, $desc ?: null, $id]);
    json_ok(['updated' => $id]);
}

if ($action === 'delete') {
    $id = (int) ($body['id'] ?? 0);
    if (!$id) { json_fail('id required.', 400); }
    $row = $pdo->prepare('SELECT class_subject_id FROM syllabus WHERE id=?');
    $row->execute([$id]); $row = $row->fetch();
    if (!$row) { json_fail('Not found.', 404); }
    $assertOwner((int)$row['class_subject_id']);
    $pdo->prepare('DELETE FROM syllabus WHERE id=?')->execute([$id]);
    json_ok(['deleted' => $id]);
}

json_fail('Unknown action.', 400);
