<?php
/**
 * CASPA School OS – /api/exams/subjects.php
 *
 * GET  ?exam_id=  – list exam_subjects for this exam
 * POST {action:"add", exam_id, class_subject_id, max_marks, exam_date}  – admin/principal
 * POST {action:"remove", exam_subject_id}                                – admin/principal
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
    $examId = (int) ($_GET['exam_id'] ?? 0);
    if (!$examId) { json_fail('exam_id required.', 400); }
    $st = $pdo->prepare('
        SELECT es.id, es.exam_id, es.class_subject_id, es.max_marks, es.exam_date,
               s.name AS subject_name, c.name AS class_name, c.section
        FROM   exam_subjects es
        JOIN   class_subjects cs ON cs.id = es.class_subject_id
        JOIN   subjects s ON s.id = cs.subject_id
        JOIN   classes  c ON c.id = cs.class_id
        WHERE  es.exam_id = ?
        ORDER  BY c.name, c.section, s.name
    ');
    $st->execute([$examId]);
    json_ok($st->fetchAll());
}

/* ── POST ────────────────────────────────────────────────────────────────── */
require_role(['admin', 'principal']);
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

if ($action === 'add') {
    $examId  = (int) ($body['exam_id']          ?? 0);
    $csId    = (int) ($body['class_subject_id'] ?? 0);
    $maxMk   = (float) ($body['max_marks']      ?? 100);
    $date    = trim($body['exam_date']           ?? '');

    if (!$examId || !$csId)  { json_fail('exam_id and class_subject_id required.', 400); }
    if ($maxMk <= 0)         { json_fail('max_marks must be positive.', 400); }
    if (!$date || !strtotime($date)) { json_fail('exam_date is invalid.', 400); }

    $st = $pdo->prepare('INSERT INTO exam_subjects (exam_id, class_subject_id, max_marks, exam_date) VALUES (?,?,?,?)');
    try {
        $st->execute([$examId, $csId, $maxMk, $date]);
    } catch (\PDOException $e) {
        json_fail('This subject is already added to this exam.', 409);
    }
    json_ok(['id' => (int)$pdo->lastInsertId()], 201);
}

if ($action === 'remove') {
    $esId = (int) ($body['exam_subject_id'] ?? 0);
    if (!$esId) { json_fail('exam_subject_id required.', 400); }
    $pdo->prepare('DELETE FROM exam_subjects WHERE id = ?')->execute([$esId]);
    json_ok(['deleted' => $esId]);
}

json_fail('Unknown action.', 400);
