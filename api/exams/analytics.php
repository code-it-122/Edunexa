<?php
/**
 * CASPA School OS – /api/exams/analytics.php
 * GET ?exam_subject_id= – class average, per-student performance
 * GET ?exam_id=&class_id= – per-subject summary for a class in an exam
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

$user = current_user();
$pdo  = db();

$esId   = (int) ($_GET['exam_subject_id'] ?? 0);
$examId = (int) ($_GET['exam_id']         ?? 0);
$clsId  = (int) ($_GET['class_id']        ?? 0);

if ($esId) {
    // Per-exam-subject analytics
    $meta = $pdo->prepare('
        SELECT es.max_marks, cs.teacher_id, c.id AS class_id, s.name AS subject_name,
               c.name AS class_name, c.section
        FROM   exam_subjects es
        JOIN   class_subjects cs ON cs.id = es.class_subject_id
        JOIN   subjects s ON s.id = cs.subject_id
        JOIN   classes c ON c.id = cs.class_id
        WHERE  es.id = ?
    ');
    $meta->execute([$esId]);
    $info = $meta->fetch();
    if (!$info) { json_fail('Exam subject not found.', 404); }

    if ($user['role'] === 'teacher' && (int)$info['teacher_id'] !== $user['id']) {
        json_fail('Forbidden.', 403);
    }

    $marks = $pdo->prepare('
        SELECT em.marks, u.name AS student_name
        FROM   exam_marks em
        JOIN   students s ON s.id = em.student_id
        JOIN   users u ON u.id = s.user_id
        WHERE  em.exam_subject_id = ?
        ORDER  BY em.marks DESC
    ');
    $marks->execute([$esId]);
    $rows = $marks->fetchAll();

    $values = array_filter(array_column($rows, 'marks'), fn($v) => $v !== null);
    $count  = count($values);
    $avg    = $count ? round(array_sum($values) / $count, 2) : null;
    $highest = $count ? max($values) : null;
    $lowest  = $count ? min($values) : null;

    json_ok([
        'info'    => $info,
        'stats'   => compact('avg', 'highest', 'lowest', 'count'),
        'records' => $rows,
    ]);
}

if ($examId && $clsId) {
    // Per-class, per-subject summary
    $rows = $pdo->prepare('
        SELECT s.name AS subject_name,
               es.max_marks,
               COUNT(em.id) AS entries,
               ROUND(AVG(em.marks), 2) AS avg_marks,
               MAX(em.marks) AS highest,
               MIN(em.marks) AS lowest
        FROM   exam_subjects es
        JOIN   class_subjects cs ON cs.id = es.class_subject_id
        JOIN   subjects s ON s.id = cs.subject_id
        LEFT JOIN exam_marks em ON em.exam_subject_id = es.id
        WHERE  es.exam_id = ? AND cs.class_id = ?
        GROUP  BY es.id, s.name, es.max_marks
        ORDER  BY s.name
    ');
    $rows->execute([$examId, $clsId]);
    json_ok($rows->fetchAll());
}

json_fail('exam_subject_id or (exam_id + class_id) required.', 400);
