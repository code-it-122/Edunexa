<?php
/**
 * CASPA School OS – /api/exams/marks.php
 *
 * GET  ?exam_subject_id=  – marks grid for a given exam_subject
 *   allowed: the teacher assigned to that class_subject, admin, principal
 *   students see only published exams (own marks); parents see linked child (published only)
 *
 * POST {exam_subject_id, records:[{student_id, marks, remarks}]}
 *   allowed: teacher assigned to class_subject, admin, principal
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
    $esId = (int) ($_GET['exam_subject_id'] ?? 0);
    if (!$esId) { json_fail('exam_subject_id required.', 400); }

    // Fetch exam_subject meta
    $meta = $pdo->prepare('
        SELECT es.*, e.is_published, e.name AS exam_name, e.academic_year_id,
               s.name AS subject_name, c.name AS class_name, c.section, c.id AS class_id,
               cs.teacher_id
        FROM   exam_subjects es
        JOIN   exams e   ON e.id = es.exam_id
        JOIN   class_subjects cs ON cs.id = es.class_subject_id
        JOIN   subjects s ON s.id = cs.subject_id
        JOIN   classes  c ON c.id = cs.class_id
        WHERE  es.id = ?
    ');
    $meta->execute([$esId]);
    $info = $meta->fetch();
    if (!$info) { json_fail('Exam subject not found.', 404); }

    // Auth check
    $isAdminPrincipal = in_array($user['role'], ['admin', 'principal'], true);
    $isAssignedTeacher = $user['role'] === 'teacher' && (int)$info['teacher_id'] === $user['id'];

    if ($user['role'] === 'student') {
        if (!$info['is_published']) { json_fail('Results not published.', 403); }
        // Check student belongs to class
        $sRow = $pdo->prepare('SELECT id FROM students WHERE user_id = ? AND class_id = ? LIMIT 1');
        $sRow->execute([$user['id'], $info['class_id']]);
        if (!$sRow->fetch()) { json_fail('Forbidden.', 403); }
    } elseif ($user['role'] === 'parent') {
        if (!$info['is_published']) { json_fail('Results not published.', 403); }
    } elseif (!$isAdminPrincipal && !$isAssignedTeacher) {
        json_fail('Forbidden.', 403);
    }

    // Fetch students + their marks
    $st = $pdo->prepare('
        SELECT s.id AS student_id, u.name AS student_name, s.admission_number,
               em.id AS mark_id, em.marks, em.remarks
        FROM   students s
        JOIN   users u ON u.id = s.user_id
        LEFT JOIN exam_marks em ON em.exam_subject_id = ? AND em.student_id = s.id
        WHERE  s.class_id = ?
        ORDER  BY u.name
    ');
    // For student: only their own row
    if ($user['role'] === 'student') {
        $sRow2 = $pdo->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
        $sRow2->execute([$user['id']]);
        $myId = $sRow2->fetchColumn();
        $st = $pdo->prepare('
            SELECT s.id AS student_id, u.name AS student_name, s.admission_number,
                   em.id AS mark_id, em.marks, em.remarks
            FROM   students s
            JOIN   users u ON u.id = s.user_id
            LEFT JOIN exam_marks em ON em.exam_subject_id = ? AND em.student_id = s.id
            WHERE  s.id = ?
        ');
        $st->execute([$esId, $myId]);
    } else {
        $st->execute([$esId, $info['class_id']]);
    }
    $marks = $st->fetchAll();

    // Compute grade bands
    $bands = $pdo->prepare('SELECT * FROM grade_bands WHERE academic_year_id = ? ORDER BY min_pct DESC');
    $bands->execute([$info['academic_year_id']]);
    $gradeBands = $bands->fetchAll();

    // Attach grade letter to each mark row
    foreach ($marks as &$m) {
        $m['grade'] = null;
        if ($m['marks'] !== null) {
            $pct = ($m['marks'] / $info['max_marks']) * 100;
            foreach ($gradeBands as $band) {
                if ($pct >= (float)$band['min_pct'] && $pct <= (float)$band['max_pct']) {
                    $m['grade'] = $band['grade_letter'];
                    break;
                }
            }
        }
    }
    unset($m);

    json_ok(['info' => $info, 'marks' => $marks]);
}

/* ── POST ────────────────────────────────────────────────────────────────── */
if ($method === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $esId  = (int) ($body['exam_subject_id'] ?? 0);
    $recs  = $body['records'] ?? [];

    if (!$esId)            { json_fail('exam_subject_id required.', 400); }
    if (!is_array($recs))  { json_fail('records must be an array.', 400); }

    // Load exam_subject meta
    $meta = $pdo->prepare('
        SELECT es.max_marks, cs.teacher_id, c.id AS class_id, e.is_published
        FROM   exam_subjects es
        JOIN   class_subjects cs ON cs.id = es.class_subject_id
        JOIN   classes c ON c.id = cs.class_id
        JOIN   exams e ON e.id = es.exam_id
        WHERE  es.id = ?
    ');
    $meta->execute([$esId]);
    $info = $meta->fetch();
    if (!$info) { json_fail('Exam subject not found.', 404); }

    $isAdminPrincipal  = in_array($user['role'], ['admin', 'principal'], true);
    $isAssignedTeacher = $user['role'] === 'teacher' && (int)$info['teacher_id'] === $user['id'];
    if (!$isAdminPrincipal && !$isAssignedTeacher) { json_fail('Forbidden.', 403); }

    $max = (float) $info['max_marks'];

    // Validate student IDs belong to class
    $validStudents = $pdo->prepare('SELECT id FROM students WHERE class_id = ?');
    $validStudents->execute([$info['class_id']]);
    $classStudentIds = array_column($validStudents->fetchAll(), 'id', 'id');

    $pdo->beginTransaction();
    $saved = 0;
    try {
        $upsert = $pdo->prepare('
            INSERT INTO exam_marks (exam_subject_id, student_id, marks, remarks, entered_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE marks=VALUES(marks), remarks=VALUES(remarks), entered_by=VALUES(entered_by)
        ');
        foreach ($recs as $rec) {
            $sid     = (int) ($rec['student_id'] ?? 0);
            $marks   = $rec['marks'] === '' || $rec['marks'] === null ? null : (float) $rec['marks'];
            $remarks = trim($rec['remarks'] ?? '');

            if (!isset($classStudentIds[$sid])) { json_fail("Student {$sid} not in this class.", 422); }
            if ($marks !== null && ($marks < 0 || $marks > $max)) {
                json_fail("Marks {$marks} out of range 0..{$max}.", 422);
            }

            $upsert->execute([$esId, $sid, $marks, $remarks ?: null, $user['id']]);
            $saved++;
        }
        $pdo->commit();
    } catch (\Exception $e) {
        $pdo->rollBack();
        json_fail('Save failed: ' . $e->getMessage(), 500);
    }

    json_ok(['saved' => $saved]);
}

json_fail('Method not allowed.', 405);
