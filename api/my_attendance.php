<?php
/**
 * CASPA School OS – api/my_attendance.php
 *
 * GET [?student_id=]   student + parent only.
 *   student → always their own record
 *   parent  → one of their linked children (defaults to the first); any other id → 403
 *
 * Returns the child's attendance: overall summary, a per-subject breakdown,
 * and the 60 most recent records. Rate = present / all records.
 */

declare(strict_types=1);

header('Content-Type: application/json');

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/response.php';

session_boot();
require_login();
require_role(['student', 'parent']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_fail('Method not allowed.', 405);
}

$user = current_user();
$pdo  = db();

$allowedIds = restricted_student_ids($user) ?? [];
if (!$allowedIds) {
    json_ok(['children' => [], 'selected' => null, 'summary' => null, 'by_subject' => [], 'records' => []]);
}

$in = implode(',', array_fill(0, count($allowedIds), '?'));
$st = $pdo->prepare("
    SELECT s.id, s.admission_number, u.name,
           c.name AS class_name, c.section AS class_section
    FROM students s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN classes c ON c.id = s.class_id
    WHERE s.id IN ($in)
    ORDER BY u.name ASC
");
$st->execute($allowedIds);
$kids = $st->fetchAll();

$requested = isset($_GET['student_id']) && $_GET['student_id'] !== '' ? (int) $_GET['student_id'] : null;
if ($requested !== null && !in_array($requested, $allowedIds, true)) {
    json_fail('Forbidden: you do not have permission to view this student.', 403);
}
$selected = $kids[0];
if ($requested !== null) {
    foreach ($kids as $k) {
        if ((int) $k['id'] === $requested) { $selected = $k; break; }
    }
}
$sid = (int) $selected['id'];

// Overall
$st = $pdo->prepare("
    SELECT COUNT(*) AS total,
           COALESCE(SUM(status = 'present'), 0) AS present,
           COALESCE(SUM(status = 'absent'),  0) AS absent,
           COALESCE(SUM(status = 'late'),    0) AS late,
           COALESCE(SUM(status = 'excused'), 0) AS excused
    FROM attendance WHERE student_id = :sid
");
$st->execute([':sid' => $sid]);
$sum   = $st->fetch();
$total = (int) $sum['total'];

// Per subject
$st = $pdo->prepare("
    SELECT s.name AS subject_name,
           COUNT(*) AS total,
           SUM(a.status = 'present') AS present,
           SUM(a.status = 'absent')  AS absent,
           SUM(a.status = 'late')    AS late,
           SUM(a.status = 'excused') AS excused
    FROM attendance a
    JOIN class_subjects cs ON cs.id = a.class_subject_id
    JOIN subjects s ON s.id = cs.subject_id
    WHERE a.student_id = :sid
    GROUP BY s.id, s.name
    ORDER BY s.name ASC
");
$st->execute([':sid' => $sid]);
$bySubject = [];
foreach ($st->fetchAll() as $r) {
    $t = (int) $r['total'];
    $bySubject[] = [
        'subject_name' => $r['subject_name'],
        'total'   => $t,
        'present' => (int) $r['present'],
        'absent'  => (int) $r['absent'],
        'late'    => (int) $r['late'],
        'excused' => (int) $r['excused'],
        'rate'    => $t > 0 ? round(((int) $r['present'] / $t) * 100, 1) : null,
    ];
}

// Recent records
$st = $pdo->prepare("
    SELECT a.date, a.status, s.name AS subject_name
    FROM attendance a
    JOIN class_subjects cs ON cs.id = a.class_subject_id
    JOIN subjects s ON s.id = cs.subject_id
    WHERE a.student_id = :sid
    ORDER BY a.date DESC, a.id DESC
    LIMIT 60
");
$st->execute([':sid' => $sid]);

json_ok([
    'children' => array_map(fn($k) => [
        'id'   => (int) $k['id'],
        'name' => $k['name'],
        'class_name' => trim(($k['class_name'] ?? '') . ' - ' . ($k['class_section'] ?? ''), ' -'),
    ], $kids),
    'selected' => [
        'id'               => $sid,
        'name'             => $selected['name'],
        'admission_number' => $selected['admission_number'],
        'class_name'       => trim(($selected['class_name'] ?? '') . ' - ' . ($selected['class_section'] ?? ''), ' -'),
    ],
    'summary' => [
        'total'   => $total,
        'present' => (int) $sum['present'],
        'absent'  => (int) $sum['absent'],
        'late'    => (int) $sum['late'],
        'excused' => (int) $sum['excused'],
        'rate'    => $total > 0 ? round(((int) $sum['present'] / $total) * 100, 1) : null,
    ],
    'by_subject' => $bySubject,
    'records'    => $st->fetchAll(),
]);
