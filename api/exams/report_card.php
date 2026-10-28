<?php
/**
 * CASPA School OS – /api/exams/report_card.php
 * GET ?student_id= [&exam_id=]
 * Returns the full report card for a student across one exam (or all published exams).
 * Scope: admin/principal see any; teacher sees own class; student sees self (published only);
 *        parent sees linked child (published only).
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();

$user      = current_user();
$pdo       = db();
$studentId = (int) ($_GET['student_id'] ?? 0);
$examId    = (int) ($_GET['exam_id']    ?? 0);

if (!$studentId) { json_fail('student_id required.', 400); }

// Load student info
$stRow = $pdo->prepare('SELECT s.*, u.name, u.email FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?');
$stRow->execute([$studentId]);
$student = $stRow->fetch();
if (!$student) { json_fail('Student not found.', 404); }

// Scope enforcement
switch ($user['role']) {
    case 'student':
        $own = $pdo->prepare('SELECT id FROM students WHERE user_id=? LIMIT 1');
        $own->execute([$user['id']]);
        if ((int)$own->fetchColumn() !== $studentId) { json_fail('Forbidden.', 403); }
        $publishedOnly = true;
        break;
    case 'parent':
        $chk = $pdo->prepare('SELECT 1 FROM parent_student ps JOIN parents p ON p.id=ps.parent_id WHERE p.user_id=? AND ps.student_id=? LIMIT 1');
        $chk->execute([$user['id'], $studentId]);
        if (!$chk->fetchColumn()) { json_fail('Forbidden.', 403); }
        $publishedOnly = true;
        break;
    case 'teacher':
        // Teacher may see class where they teach
        $chk = $pdo->prepare('SELECT 1 FROM class_subjects WHERE class_id=? AND teacher_id=? LIMIT 1');
        $chk->execute([$student['class_id'], $user['id']]);
        if (!$chk->fetchColumn()) { json_fail('Forbidden.', 403); }
        $publishedOnly = false;
        break;
    case 'admin':
    case 'principal':
        $publishedOnly = false;
        break;
    default:
        json_fail('Forbidden.', 403);
}

$pubFilter = $publishedOnly ? 'AND e.is_published = 1' : '';
$examFilter = $examId ? 'AND e.id = ' . $examId : '';

$rows = $pdo->prepare("
    SELECT e.id AS exam_id, e.name AS exam_name, e.term, e.exam_type, e.is_published,
           es.id AS exam_subject_id, es.max_marks, es.exam_date,
           s.name AS subject_name,
           em.marks, em.remarks
    FROM   exam_subjects es
    JOIN   exams e ON e.id = es.exam_id
    JOIN   class_subjects cs ON cs.id = es.class_subject_id
    JOIN   subjects s ON s.id = cs.subject_id
    LEFT JOIN exam_marks em ON em.exam_subject_id = es.id AND em.student_id = ?
    WHERE  cs.class_id = ? {$pubFilter} {$examFilter}
    ORDER  BY e.id, s.name
");
$rows->execute([$studentId, $student['class_id']]);
$results = $rows->fetchAll();

// Grade bands
$ay  = $pdo->prepare('SELECT id FROM academic_years WHERE is_current=1 LIMIT 1');
$ay->execute();
$ayId = (int) $ay->fetchColumn();
$bands = $pdo->prepare('SELECT * FROM grade_bands WHERE academic_year_id=? ORDER BY min_pct DESC');
$bands->execute([$ayId]);
$gradeBands = $bands->fetchAll();

$calcGrade = function(?float $marks, float $max) use ($gradeBands): ?string {
    if ($marks === null) return null;
    $pct = ($marks / $max) * 100;
    foreach ($gradeBands as $b) {
        if ($pct >= (float)$b['min_pct'] && $pct <= (float)$b['max_pct']) return $b['grade_letter'];
    }
    return 'F';
};

// Attach grades
foreach ($results as &$r) {
    $r['grade'] = $calcGrade(
        $r['marks'] !== null ? (float)$r['marks'] : null,
        (float)$r['max_marks']
    );
}
unset($r);

// Group by exam
$byExam = [];
foreach ($results as $r) {
    $eid = $r['exam_id'];
    if (!isset($byExam[$eid])) {
        $byExam[$eid] = [
            'exam_id'   => $r['exam_id'],
            'exam_name' => $r['exam_name'],
            'term'      => $r['term'],
            'exam_type' => $r['exam_type'],
            'subjects'  => [],
            'total_marks'   => 0,
            'total_max'     => 0,
            'overall_grade' => null,
        ];
    }
    $byExam[$eid]['subjects'][] = $r;
    if ($r['marks'] !== null)   $byExam[$eid]['total_marks'] += (float)$r['marks'];
    if ($r['max_marks'] !== null) $byExam[$eid]['total_max'] += (float)$r['max_marks'];
}

// Compute overall grade for each exam
foreach ($byExam as &$exam) {
    if ($exam['total_max'] > 0) {
        $exam['overall_grade'] = $calcGrade($exam['total_marks'], $exam['total_max']);
        $exam['overall_pct']   = round(($exam['total_marks'] / $exam['total_max']) * 100, 2);
    }
}
unset($exam);

$classRow = $pdo->prepare('SELECT name, section FROM classes WHERE id=?');
$classRow->execute([$student['class_id']]);
$classInfo = $classRow->fetch();

json_ok([
    'student'   => $student,
    'class'     => $classInfo,
    'exams'     => array_values($byExam),
]);
