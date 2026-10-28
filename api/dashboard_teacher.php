<?php
/**
 * CASPA School OS – api/dashboard_teacher.php
 *
 * GET:
 * Returns teacher dashboard real data:
 * - assigned classes with student counts
 * - today's attendance status per class (marked / not marked)
 * - upcoming assignments (next 5 by due date)
 * - latest 3 announcements for teachers
 * - summary KPI metrics
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
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_fail('Method not allowed.', 405);
}

$user  = current_user();
$pdo   = db();
$uid   = (int) $user['id'];
$today = date('Y-m-d');

// ── 1. Assigned classes & today's attendance per class ────────────────────────
$classesSql = "
    SELECT DISTINCT c.id as class_id, c.name as class_name, c.section as class_section,
           c.class_teacher_id
    FROM classes c
    JOIN class_subjects cs ON cs.class_id = c.id
    WHERE cs.teacher_id = :uid
    ORDER BY c.name ASC, c.section ASC
";
$stClasses = $pdo->prepare($classesSql);
$stClasses->execute([':uid' => $uid]);
$rawClasses = $stClasses->fetchAll();

$assignedClasses = [];
$totalStudentsTaught = 0;
$classesMarkedToday = 0;
$classesUnmarkedToday = 0;

$studentsCountSt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id = :cid");
$subjectsSt = $pdo->prepare("
    SELECT cs.id as class_subject_id, s.id as subject_id, s.name as subject_name, s.code as subject_code
    FROM class_subjects cs
    JOIN subjects s ON s.id = cs.subject_id
    WHERE cs.class_id = :cid AND cs.teacher_id = :uid
    ORDER BY s.name ASC
");
$attendanceSt = $pdo->prepare("
    SELECT COUNT(DISTINCT a.student_id) as marked_students
    FROM attendance a
    JOIN class_subjects cs ON cs.id = a.class_subject_id
    WHERE cs.class_id = :cid
      AND cs.teacher_id = :uid
      AND a.date = :today
");

foreach ($rawClasses as $c) {
    $cid = (int) $c['class_id'];

    // Student count for this class
    $studentsCountSt->execute([':cid' => $cid]);
    $studentCount = (int) $studentsCountSt->fetchColumn();
    $totalStudentsTaught += $studentCount;

    // Subjects taught by teacher in this class
    $subjectsSt->execute([':cid' => $cid, ':uid' => $uid]);
    $subjects = $subjectsSt->fetchAll();

    // Attendance marked today for this teacher's class
    $attendanceSt->execute([':cid' => $cid, ':uid' => $uid, ':today' => $today]);
    $markedStudentsCount = (int) $attendanceSt->fetchColumn();

    $isMarked = ($markedStudentsCount > 0);
    $statusString = $isMarked ? 'marked' : 'not marked';

    if ($isMarked) {
        $classesMarkedToday++;
    } else {
        $classesUnmarkedToday++;
    }

    $firstCsId = !empty($subjects) ? (int) $subjects[0]['class_subject_id'] : null;

    $assignedClasses[] = [
        'class_id'               => $cid,
        'class_name'             => $c['class_name'],
        'class_section'          => $c['class_section'],
        'display_name'           => $c['class_name'] . ' - ' . $c['class_section'],
        'is_class_teacher'       => ((int) $c['class_teacher_id'] === $uid),
        'student_count'          => $studentCount,
        'subjects'               => $subjects,
        'today_attendance_status'=> $statusString,
        'is_attendance_marked'   => $isMarked,
        'marked_students_count'  => $markedStudentsCount,
        'first_class_subject_id' => $firstCsId,
    ];
}

// ── 2. Upcoming assignments (next 5 by due date) ─────────────────────────────
$assignmentsSql = "
    SELECT a.id, a.title, a.description, a.due_date, a.max_marks,
           cs.id as class_subject_id, cs.class_id,
           c.name as class_name, c.section as class_section,
           s.name as subject_name, s.code as subject_code,
           CASE WHEN a.due_date < CURDATE() THEN 1 ELSE 0 END as is_overdue,
           CASE WHEN a.due_date = CURDATE() THEN 1 ELSE 0 END as is_due_today
    FROM assignments a
    JOIN class_subjects cs ON cs.id = a.class_subject_id
    JOIN classes c ON c.id = cs.class_id
    JOIN subjects s ON s.id = cs.subject_id
    WHERE (cs.teacher_id = :t_id1 OR a.created_by = :t_id2)
    ORDER BY (CASE WHEN a.due_date >= CURDATE() THEN 0 ELSE 1 END) ASC,
             a.due_date ASC,
             a.id DESC
    LIMIT 5
";
$stAssignments = $pdo->prepare($assignmentsSql);
$stAssignments->execute([':t_id1' => $uid, ':t_id2' => $uid]);
$upcomingAssignments = $stAssignments->fetchAll();

// Total active/upcoming assignments count
$activeAsgnCountSt = $pdo->prepare("
    SELECT COUNT(*)
    FROM assignments a
    JOIN class_subjects cs ON cs.id = a.class_subject_id
    WHERE (cs.teacher_id = :t_id1 OR a.created_by = :t_id2)
      AND a.due_date >= CURDATE()
");
$activeAsgnCountSt->execute([':t_id1' => $uid, ':t_id2' => $uid]);
$activeAssignmentsCount = (int) $activeAsgnCountSt->fetchColumn();

// ── 3. Latest 3 announcements for teachers ──────────────────────────────────
$announcementsSql = "
    SELECT a.id, a.title, a.body, a.published_at, u.name as author_name
    FROM announcements a
    JOIN users u ON u.id = a.created_by
    WHERE FIND_IN_SET('teacher', a.audience) > 0
      AND (a.published_at IS NOT NULL AND a.published_at <= NOW())
      AND (a.expires_at IS NULL OR a.expires_at >= NOW())
    ORDER BY a.published_at DESC, a.id DESC
    LIMIT 3
";
$stAnnouncements = $pdo->prepare($announcementsSql);
$stAnnouncements->execute();
$announcements = $stAnnouncements->fetchAll();

// ── 4. Return complete dashboard payload ─────────────────────────────────────
json_ok([
    'teacher' => [
        'id'    => $uid,
        'name'  => $user['name'],
        'email' => $user['email'],
    ],
    'today' => $today,
    'kpis'  => [
        'assigned_classes_count'    => count($assignedClasses),
        'total_students_count'      => $totalStudentsTaught,
        'classes_marked_today'      => $classesMarkedToday,
        'classes_unmarked_today'    => $classesUnmarkedToday,
        'active_assignments_count'  => $activeAssignmentsCount,
    ],
    'assigned_classes'      => $assignedClasses,
    'upcoming_assignments' => $upcomingAssignments,
    'announcements'        => $announcements,
]);
