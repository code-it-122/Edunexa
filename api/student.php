<?php
/**
 * CASPA School OS – api/student.php
 *
 * GET ?id=
 * Returns detailed student profile, linked parents, and attendance summary.
 * Enforces scope via can_view_student(): teachers by class, students only themselves,
 * parents only their linked children. Returns 403 otherwise.
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

$user = current_user();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_fail('Method not allowed.', 405);
}

$studentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($studentId <= 0) {
    json_fail('A valid student ID is required.', 422);
}

// 1. Fetch student
$sql = "
    SELECT s.id, s.user_id, s.class_id, s.admission_number, s.date_of_birth, s.gender, s.address, s.created_at,
           u.name, u.email, u.is_active,
           c.name as class_name, c.section as class_section,
           ay.label as academic_year,
           ct.name as class_teacher_name
    FROM students s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN classes c ON c.id = s.class_id
    LEFT JOIN academic_years ay ON ay.id = c.academic_year_id
    LEFT JOIN users ct ON ct.id = c.class_teacher_id
    WHERE s.id = :id
    LIMIT 1
";
$st = $pdo->prepare($sql);
$st->execute([':id' => $studentId]);
$student = $st->fetch();

if (!$student) {
    json_fail('Student not found.', 404);
}

// 2. Security Scope Check
if (!can_view_student($user, $student)) {
    json_fail('Forbidden: you do not have permission to view this student.', 403);
}

// 3. Fetch linked parents
$parentSql = "
    SELECT p.id, p.phone, p.occupation, u.name, u.email, ps.relation
    FROM parent_student ps
    JOIN parents p ON p.id = ps.parent_id
    JOIN users u ON u.id = p.user_id
    WHERE ps.student_id = :id
    ORDER BY u.name ASC
";
$parentSt = $pdo->prepare($parentSql);
$parentSt->execute([':id' => $studentId]);
$parents = $parentSt->fetchAll();

// 4. Attendance summary & recent records
$attSql = "
    SELECT
        COUNT(*) as total_days,
        COALESCE(SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END), 0) as present_days,
        COALESCE(SUM(CASE WHEN a.status = 'absent'  THEN 1 ELSE 0 END), 0) as absent_days,
        COALESCE(SUM(CASE WHEN a.status = 'late'    THEN 1 ELSE 0 END), 0) as late_days,
        COALESCE(SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END), 0) as excused_days
    FROM attendance a
    WHERE a.student_id = :id
";
$attSt = $pdo->prepare($attSql);
$attSt->execute([':id' => $studentId]);
$attSummary = $attSt->fetch();

$totalDays   = (int) ($attSummary['total_days'] ?? 0);
$presentDays = (int) ($attSummary['present_days'] ?? 0);
$rate        = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0.0;

// Recent attendance records
$recentSql = "
    SELECT a.id, a.date, a.status, sub.name as subject_name, sub.code as subject_code,
           rec.name as recorded_by_name
    FROM attendance a
    JOIN class_subjects cs ON cs.id = a.class_subject_id
    JOIN subjects sub ON sub.id = cs.subject_id
    JOIN users rec ON rec.id = a.recorded_by
    WHERE a.student_id = :id
    ORDER BY a.date DESC
    LIMIT 10
";
$recentSt = $pdo->prepare($recentSql);
$recentSt->execute([':id' => $studentId]);
$recentRecords = $recentSt->fetchAll();

json_ok([
    'student'    => $student,
    'parents'    => $parents,
    'attendance' => [
        'total_days'   => $totalDays,
        'present_days' => $presentDays,
        'absent_days'  => (int) ($attSummary['absent_days'] ?? 0),
        'late_days'    => (int) ($attSummary['late_days'] ?? 0),
        'excused_days' => (int) ($attSummary['excused_days'] ?? 0),
        'rate'         => $rate,
        'recent'       => $recentRecords,
    ],
]);
