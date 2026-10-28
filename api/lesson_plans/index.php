<?php
/**
 * CASPA School OS – /api/lesson_plans/index.php
 *
 * GET  ?class_subject_id= [&status=]  – list lesson plans
 *      admin/principal: all; teacher: own; others: 403
 *
 * POST actions:
 *   create  {class_subject_id, title, planned_date, objectives, content, materials, syllabus_id?}
 *   update  {id, title, planned_date, objectives, content, materials, status?}
 *   delete  {id}
 *   review  {id, status:"approved"|"rejected", review_notes?} – principal/admin only
 *   submit  {id} – teacher submits a draft
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();
require_role(['admin', 'principal', 'teacher']);

$user   = current_user();
$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* ── GET ─────────────────────────────────────────────────────────────────── */
if ($method === 'GET') {
    $csId   = (int) ($_GET['class_subject_id'] ?? 0);
    $status = trim($_GET['status'] ?? '');

    $where   = [];
    $params  = [];

    if ($csId) {
        $where[]  = 'lp.class_subject_id = ?';
        $params[] = $csId;
    }

    // Teacher sees only their own
    if ($user['role'] === 'teacher') {
        $where[]  = 'lp.created_by = ?';
        $params[] = $user['id'];
    }

    if ($status) {
        $where[]  = 'lp.status = ?';
        $params[] = $status;
    }

    $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $rows = $pdo->prepare("
        SELECT lp.*, s.name AS subject_name, c.name AS class_name, c.section,
               creator.name AS created_by_name,
               reviewer.name AS reviewed_by_name,
               syl.title AS syllabus_unit_title
        FROM   lesson_plans lp
        JOIN   class_subjects cs ON cs.id = lp.class_subject_id
        JOIN   subjects s ON s.id = cs.subject_id
        JOIN   classes  c ON c.id = cs.class_id
        JOIN   users creator ON creator.id = lp.created_by
        LEFT JOIN users reviewer ON reviewer.id = lp.reviewed_by
        LEFT JOIN syllabus syl ON syl.id = lp.syllabus_id
        {$whereStr}
        ORDER  BY lp.planned_date DESC, lp.created_at DESC
    ");
    $rows->execute($params);
    json_ok($rows->fetchAll());
}

/* ── POST ────────────────────────────────────────────────────────────────── */
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

$assertOwnerLP = function(int $lpId) use ($user, $pdo): array {
    $r = $pdo->prepare('SELECT lp.*, cs.teacher_id FROM lesson_plans lp JOIN class_subjects cs ON cs.id=lp.class_subject_id WHERE lp.id=?');
    $r->execute([$lpId]); $r = $r->fetch();
    if (!$r) { json_fail('Lesson plan not found.', 404); }
    if ($user['role'] === 'teacher' && (int)$r['teacher_id'] !== $user['id']) { json_fail('Forbidden.', 403); }
    return $r;
};

if ($action === 'create') {
    $csId   = (int) ($body['class_subject_id'] ?? 0);
    $title  = trim($body['title']        ?? '');
    $date   = trim($body['planned_date'] ?? '');
    $obj    = trim($body['objectives']   ?? '');
    $cnt    = trim($body['content']      ?? '');
    $mat    = trim($body['materials']    ?? '');
    $sylId  = isset($body['syllabus_id']) && $body['syllabus_id'] ? (int)$body['syllabus_id'] : null;

    if (!$csId || !$title || !$date) { json_fail('class_subject_id, title and planned_date required.', 400); }
    if (!strtotime($date)) { json_fail('Invalid planned_date.', 400); }

    // Teacher must own this cs
    if ($user['role'] === 'teacher') {
        $chk = $pdo->prepare('SELECT teacher_id FROM class_subjects WHERE id=?');
        $chk->execute([$csId]);
        $r = $chk->fetch();
        if (!$r || (int)$r['teacher_id'] !== $user['id']) { json_fail('Forbidden.', 403); }
    }

    $st = $pdo->prepare('INSERT INTO lesson_plans (class_subject_id,syllabus_id,title,planned_date,objectives,content,materials,created_by) VALUES (?,?,?,?,?,?,?,?)');
    $st->execute([$csId, $sylId, $title, $date, $obj ?: null, $cnt ?: null, $mat ?: null, $user['id']]);
    json_ok(['id' => (int)$pdo->lastInsertId()], 201);
}

if ($action === 'update') {
    $id    = (int) ($body['id']           ?? 0);
    if (!$id) { json_fail('id required.', 400); }
    $lp = $assertOwnerLP($id);
    if ($lp['status'] === 'approved' && $user['role'] === 'teacher') {
        json_fail('Cannot edit an approved plan.', 422);
    }
    $title = trim($body['title']        ?? $lp['title']);
    $date  = trim($body['planned_date'] ?? $lp['planned_date']);
    $obj   = trim($body['objectives']   ?? $lp['objectives'] ?? '');
    $cnt   = trim($body['content']      ?? $lp['content']    ?? '');
    $mat   = trim($body['materials']    ?? $lp['materials']  ?? '');
    $sylId = isset($body['syllabus_id']) && $body['syllabus_id'] ? (int)$body['syllabus_id'] : $lp['syllabus_id'];
    if (!$title || !$date) { json_fail('title and planned_date required.', 400); }
    $pdo->prepare('UPDATE lesson_plans SET title=?,planned_date=?,objectives=?,content=?,materials=?,syllabus_id=?,status=\'draft\' WHERE id=?')
        ->execute([$title, $date, $obj ?: null, $cnt ?: null, $mat ?: null, $sylId, $id]);
    json_ok(['updated' => $id]);
}

if ($action === 'delete') {
    $id = (int) ($body['id'] ?? 0);
    if (!$id) { json_fail('id required.', 400); }
    $assertOwnerLP($id);
    $pdo->prepare('DELETE FROM lesson_plans WHERE id=?')->execute([$id]);
    json_ok(['deleted' => $id]);
}

if ($action === 'submit') {
    $id = (int) ($body['id'] ?? 0);
    if (!$id) { json_fail('id required.', 400); }
    $lp = $assertOwnerLP($id);
    if ($lp['status'] !== 'draft') { json_fail('Only draft plans can be submitted.', 422); }
    $pdo->prepare('UPDATE lesson_plans SET status=\'submitted\' WHERE id=?')->execute([$id]);
    json_ok(['id' => $id, 'status' => 'submitted']);
}

if ($action === 'review') {
    require_role(['admin', 'principal']);
    $id     = (int) ($body['id']     ?? 0);
    $status = trim($body['status']   ?? '');
    $notes  = trim($body['review_notes'] ?? '');
    if (!$id || !in_array($status, ['approved', 'rejected'], true)) {
        json_fail('id and status (approved|rejected) required.', 400);
    }
    $lp = $pdo->prepare('SELECT id,status FROM lesson_plans WHERE id=?');
    $lp->execute([$id]); $lp = $lp->fetch();
    if (!$lp) { json_fail('Not found.', 404); }
    if ($lp['status'] !== 'submitted') { json_fail('Only submitted plans can be reviewed.', 422); }
    $pdo->prepare('UPDATE lesson_plans SET status=?,reviewed_by=?,reviewed_at=NOW(),review_notes=? WHERE id=?')
        ->execute([$status, $user['id'], $notes ?: null, $id]);
    json_ok(['id' => $id, 'status' => $status]);
}

json_fail('Unknown action.', 400);
