<?php
/**
 * CASPA School OS – api/dashboard_student.php
 *
 * GET:
 * Returns the authenticated student's own dashboard data:
 * - Profile card info
 * - Own attendance percentage and last 10 days
 * - Upcoming assignments (for student's class)
 * - School notices/announcements (audience includes student)
 *
 * Strictly enforced: a student can only read their own records.
 * If ?student_id= or ?id= is passed with another student's ID, returns 403 Forbidden.
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
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_fail('Method not allowed.', 405);
}

$user = current_user();
$pdo  = db();
$uid  = (int) $user['id'];

// 1. Fetch student's own record
$sql = "
    SELECT s.id, s.user_id, s.class_id, s.admission_number, s.date_of_birth, s.gender, s.address,
           u.name, u.email,
           c.name as class_name, c.section as class_section,
           ct.name as class_teacher_name,
           ay.label as academic_year
    FROM students s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN classes c ON c.id = s.class_id
    LEFT JOIN academic_years ay ON ay.id = c.academic_year_id
    LEFT JOIN users ct ON ct.id = c.class_teacher_id
    WHERE s.user_id = :uid
    LIMIT 1
";
$st = $pdo->prepare($sql);
$st->execute([':uid' => $uid]);
$student = $st->fetch();

if (!$student) {
    json_fail('Student profile not found.', 404);
}

// 2. Strict ID scope verification: student can only read their own records
$requestedId = null;
if (isset($_GET['student_id']) && $_GET['student_id'] !== '') {
    $requestedId = (int) $_GET['student_id'];
} elseif (isset($_GET['id']) && $_GET['id'] !== '') {
    $requestedId = (int) $_GET['id'];
}

if ($requestedId !== null && $requestedId !== (int) $student['id']) {
    json_fail('Forbidden: You can only access your own records.', 403);
}

$studentId = (int) $student['id'];
$classId   = $student['class_id'] !== null ? (int) $student['class_id'] : null;

// 3. Attendance summary & percentage
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
$attSt->execute([':sid' => $studentId]);
$attSummary = $attSt->fetch();

$totalDays   = (int) ($attSummary['total_days'] ?? 0);
$presentDays = (int) ($attSummary['present_days'] ?? 0);
$absentDays  = (int) ($attSummary['absent_days'] ?? 0);
$lateDays    = (int) ($attSummary['late_days'] ?? 0);
$excusedDays = (int) ($attSummary['excused_days'] ?? 0);
$percentage  = $totalDays > 0 ? round((($presentDays + $lateDays) / $totalDays) * 100, 1) : 0.0;

// 4. Last 10 days attendance records
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
$recentAttSt->execute([':sid' => $studentId]);
$recentAttendance = $recentAttSt->fetchAll();

// 5. Upcoming assignments (for student's class)
$upcomingAssignments = [];
if ($classId !== null) {
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
          AND a.due_date >= CURDATE()
        ORDER BY a.due_date ASC, a.id ASC
        LIMIT 10
    ";
    $asgnSt = $pdo->prepare($asgnSql);
    $asgnSt->execute([':cid' => $classId]);
    $upcomingAssignments = $asgnSt->fetchAll();
}

// 6. Notices (published announcements visible to students)
$noticesSql = "
    SELECT a.id, a.title, a.body, a.published_at, a.expires_at,
           u.name as author_name
    FROM announcements a
    JOIN users u ON u.id = a.created_by
    WHERE FIND_IN_SET('student', a.audience) > 0
      AND a.published_at IS NOT NULL
      AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())
    ORDER BY a.published_at DESC, a.id DESC
    LIMIT 5
";
$noticesSt = $pdo->prepare($noticesSql);
$noticesSt->execute();
$notices = $noticesSt->fetchAll();

// 7. Return payload
json_ok([
    'student' => [
        'id'                 => $studentId,
        'user_id'            => (int) $student['user_id'],
        'name'               => $student['name'],
        'email'              => $student['email'],
        'admission_number'   => $student['admission_number'],
        'class_id'           => $classId,
        'class_name'         => $student['class_name'] ?? '—',
        'class_section'      => $student['class_section'] ?? '—',
        'class_teacher_name' => $student['class_teacher_name'] ?? '—',
        'academic_year'      => $student['academic_year'] ?? '',
        'date_of_birth'      => $student['date_of_birth'],
        'gender'             => $student['gender'],
        'address'            => $student['address'],
    ],
    'attendance_summary' => [
        'total_days'   => $totalDays,
        'present_days' => $presentDays,
        'absent_days'  => $absentDays,
        'late_days'    => $lateDays,
        'excused_days' => $excusedDays,
        'percentage'   => $percentage,
    ],
    'recent_attendance'    => $recentAttendance,
    'upcoming_assignments' => $upcomingAssignments,
    'notices'              => $notices,
]);
