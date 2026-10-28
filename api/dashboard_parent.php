<?php
/**
 * CASPA School OS – api/dashboard_parent.php
 *
 * GET:
 * Returns data for parent dashboard:
 * - List of linked children via parent_student
 * - Selected child (defaults to first linked child, or by ?student_id= / ?id=)
 * - Selected child's attendance summary and last 10 days
 * - Selected child's class assignments
 * - School notices/announcements (audience includes parent)
 *
 * Strictly enforced: a parent can only read records of their linked children.
 * If ?student_id= or ?id= is passed for a child that is not linked, returns 403 Forbidden.
 */

declare(strict_types=1);

header('Content-Type: application/json');

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();
require_role('parent');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_fail('Method not allowed.', 405);
}

$user = current_user();
$pdo  = db();
$uid  = (int) $user['id'];

// 1. Fetch parent record
$parentSt = $pdo->prepare("
    SELECT p.id, p.phone, p.occupation, u.name, u.email
    FROM parents p
    JOIN users u ON u.id = p.user_id
    WHERE p.user_id = :uid
    LIMIT 1
");
$parentSt->execute([':uid' => $uid]);
$parent = $parentSt->fetch();

if (!$parent) {
    json_fail('Parent profile not found.', 404);
}

$parentId = (int) $parent['id'];

// 2. Fetch linked children via parent_student
$childrenSql = "
    SELECT s.id as student_id, s.admission_number, s.date_of_birth, s.gender, s.address,
           u.name as student_name, u.email as student_email,
           c.id as class_id, c.name as class_name, c.section as class_section,
           ct.name as class_teacher_name,
           ps.relation
    FROM parent_student ps
    JOIN parents p ON p.id = ps.parent_id
    JOIN students s ON s.id = ps.student_id
    JOIN users u ON u.id = s.user_id
    LEFT JOIN classes c ON c.id = s.class_id
    LEFT JOIN users ct ON ct.id = c.class_teacher_id
    WHERE p.id = :pid
    ORDER BY u.name ASC
";
$childrenSt = $pdo->prepare($childrenSql);
$childrenSt->execute([':pid' => $parentId]);
$children = $childrenSt->fetchAll();

if (empty($children)) {
    // Parent with no linked children yet
    json_ok([
        'children'           => [],
        'selected_child'     => null,
        'attendance_summary' => null,
        'recent_attendance'  => [],
        'assignments'        => [],
        'notices'            => [],
    ]);
}

// Map children by student_id
$childMap = [];
foreach ($children as $ch) {
    $childMap[(int) $ch['student_id']] = $ch;
}

// 3. Resolve requested child & strictly enforce server-side security
$requestedId = null;
if (isset($_GET['student_id']) && $_GET['student_id'] !== '') {
    $requestedId = (int) $_GET['student_id'];
} elseif (isset($_GET['id']) && $_GET['id'] !== '') {
    $requestedId = (int) $_GET['id'];
}

if ($requestedId !== null) {
    if (!isset($childMap[$requestedId])) {
        json_fail('Forbidden: You can only access records of your linked children.', 403);
    }
    $selectedChild = $childMap[$requestedId];
} else {
    // Default to first linked child
    $selectedChild = $children[0];
}

$selectedStudentId = (int) $selectedChild['student_id'];
$selectedClassId   = $selectedChild['class_id'] !== null ? (int) $selectedChild['class_id'] : null;

// 4. Selected child attendance summary & percentage
$attSql = "
    SELECT
        COUNT(*) as total_days,
        COALESCE(SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END), 0) as present_days,
        COALESCE(SUM(CASE WHEN a.status = 'absent'  THEN 1 ELSE 0 END), 0) as absent_days,
        COALESCE(SUM(CASE WHEN a.status = 'late'    THEN 1 ELSE 0 END), 0) as late_days,
        COALESCE(SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END), 0) as excused_days
    FROM attendance a
    WHERE a.student_id = :sid
";
$attSt = $pdo->prepare($attSql);
$attSt->execute([':sid' => $selectedStudentId]);
$attSummary = $attSt->fetch();

$totalDays   = (int) ($attSummary['total_days'] ?? 0);
$presentDays = (int) ($attSummary['present_days'] ?? 0);
$absentDays  = (int) ($attSummary['absent_days'] ?? 0);
$lateDays    = (int) ($attSummary['late_days'] ?? 0);
$excusedDays = (int) ($attSummary['excused_days'] ?? 0);
$percentage  = $totalDays > 0 ? round((($presentDays + $lateDays) / $totalDays) * 100, 1) : 0.0;

// 5. Selected child last 10 days attendance records
$recentAttSql = "
    SELECT a.id, a.date, a.status,
           s.name as subject_name, s.code as subject_code,
           u.name as recorded_by_name
    FROM attendance a
    JOIN class_subjects cs ON cs.id = a.class_subject_id
    JOIN subjects s ON s.id = cs.subject_id
    LEFT JOIN users u ON u.id = a.recorded_by
    WHERE a.student_id = :sid
    ORDER BY a.date DESC, a.id DESC
    LIMIT 10
";
$recentAttSt = $pdo->prepare($recentAttSql);
$recentAttSt->execute([':sid' => $selectedStudentId]);
$recentAttendance = $recentAttSt->fetchAll();

// 6. Selected child assignments
$assignments = [];
if ($selectedClassId !== null) {
    $asgnSql = "
        SELECT a.id, a.title, a.description, a.due_date, a.max_marks,
               s.name as subject_name, s.code as subject_code,
               u.name as teacher_name,
               DATEDIFF(a.due_date, CURDATE()) as days_until_due
        FROM assignments a
        JOIN class_subjects cs ON cs.id = a.class_subject_id
        JOIN subjects s ON s.id = cs.subject_id
        LEFT JOIN users u ON u.id = a.created_by
        WHERE cs.class_id = :cid
        ORDER BY a.due_date ASC, a.id ASC
        LIMIT 10
    ";
    $asgnSt = $pdo->prepare($asgnSql);
    $asgnSt->execute([':cid' => $selectedClassId]);
    $assignments = $asgnSt->fetchAll();
}

// 7. Notices (published announcements visible to parents)
$noticesSql = "
    SELECT a.id, a.title, a.body, a.published_at, a.expires_at,
           u.name as author_name
    FROM announcements a
    JOIN users u ON u.id = a.created_by
    WHERE FIND_IN_SET('parent', a.audience) > 0
      AND a.published_at IS NOT NULL
      AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())
    ORDER BY a.published_at DESC, a.id DESC
    LIMIT 5
";
$noticesSt = $pdo->prepare($noticesSql);
$noticesSt->execute();
$notices = $noticesSt->fetchAll();

// 8. Return formatted payload
json_ok([
    'children' => array_values(array_map(function ($ch) {
        return [
            'student_id'       => (int) $ch['student_id'],
            'student_name'     => $ch['student_name'],
            'admission_number' => $ch['admission_number'],
            'class_id'         => $ch['class_id'] !== null ? (int) $ch['class_id'] : null,
            'class_name'       => $ch['class_name'] ?? '—',
            'class_section'    => $ch['class_section'] ?? '—',
            'relation'         => $ch['relation'],
        ];
    }, $children)),
    'selected_child' => [
        'student_id'         => $selectedStudentId,
        'student_name'       => $selectedChild['student_name'],
        'student_email'      => $selectedChild['student_email'],
        'admission_number'   => $selectedChild['admission_number'],
        'class_id'           => $selectedClassId,
        'class_name'         => $selectedChild['class_name'] ?? '—',
        'class_section'      => $selectedChild['class_section'] ?? '—',
        'class_teacher_name' => $selectedChild['class_teacher_name'] ?? '—',
        'relation'           => $selectedChild['relation'],
        'date_of_birth'      => $selectedChild['date_of_birth'],
        'gender'             => $selectedChild['gender'],
        'address'            => $selectedChild['address'],
    ],
    'attendance_summary' => [
        'total_days'   => $totalDays,
        'present_days' => $presentDays,
        'absent_days'  => $absentDays,
        'late_days'    => $lateDays,
        'excused_days' => $excusedDays,
        'percentage'   => $percentage,
    ],
    'recent_attendance' => $recentAttendance,
    'assignments'       => $assignments,
    'notices'           => $notices,
]);
