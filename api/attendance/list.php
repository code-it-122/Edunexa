<?php
/**
 * CASPA School OS – api/attendance/list.php
 *
 * GET ?class_subject_id=&date=
 * Students of that class with their existing status for that date.
 * Allowed: the teacher assigned to that class_subject, admin, principal. Others get 403.
 */

declare(strict_types=1);

header('Content-Type: application/json');

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/response.php';

session_boot();
require_login();
require_role(['teacher', 'admin', 'principal']);

$user = current_user();
$pdo  = db();

$csId = isset($_GET['class_subject_id']) ? (int) $_GET['class_subject_id'] : 0;
$date = trim((string) ($_GET['date'] ?? ''));

if ($csId <= 0) {
    json_fail('A valid class_subject_id is required.', 422);
}

if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_fail('A valid date in YYYY-MM-DD format is required.', 422);
}

// 1. Fetch class_subject and check existence
$csSql = "
    SELECT cs.id, cs.class_id, cs.subject_id, cs.teacher_id,
           c.name as class_name, c.section as class_section,
           s.name as subject_name
    FROM class_subjects cs
    JOIN classes c ON c.id = cs.class_id
    JOIN subjects s ON s.id = cs.subject_id
    WHERE cs.id = :cs_id
    LIMIT 1
";
$csSt = $pdo->prepare($csSql);
$csSt->execute([':cs_id' => $csId]);
$cs = $csSt->fetch();

if (!$cs) {
    json_fail('Class subject assignment not found.', 404);
}

// 2. Authorization check: assigned teacher, admin, or principal
$isAssignedTeacher = ($user['role'] === 'teacher' && (int) $cs['teacher_id'] === (int) $user['id']);
$isAdminOrPrincipal = in_array($user['role'], ['admin', 'principal'], true);

if (!$isAssignedTeacher && !$isAdminOrPrincipal) {
    json_fail('Forbidden: you are not assigned to this class subject.', 403);
}

// 3. Fetch students belonging to that class with left join on attendance for date & class_subject_id
$studentsSql = "
    SELECT st.id as student_id,
           st.admission_number,
           u.name as student_name,
           u.email as student_email,
           att.id as attendance_id,
           att.status as attendance_status,
           att.recorded_by
    FROM students st
    JOIN users u ON u.id = st.user_id
    LEFT JOIN attendance att ON att.student_id = st.id
                            AND att.class_subject_id = :cs_id
                            AND att.date = :att_date
    WHERE st.class_id = :class_id
      AND u.is_active = 1
    ORDER BY st.admission_number ASC
";

$stSt = $pdo->prepare($studentsSql);
$stSt->execute([
    ':cs_id'    => $csId,
    ':att_date' => $date,
    ':class_id' => (int) $cs['class_id'],
]);
$students = $stSt->fetchAll();

// 4. Summaries
$counts = [
    'total'   => count($students),
    'present' => 0,
    'absent'  => 0,
    'late'    => 0,
    'excused' => 0,
    'unmarked'=> 0,
];

foreach ($students as $s) {
    $st = $s['attendance_status'];
    if ($st && isset($counts[$st])) {
        $counts[$st]++;
    } else {
        $counts['unmarked']++;
    }
}

json_ok([
    'class_subject' => $cs,
    'date'          => $date,
    'students'      => $students,
    'counts'        => $counts,
]);
