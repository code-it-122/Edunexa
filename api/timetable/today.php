<?php
/**
 * CASPA School OS – GET /api/timetable/today.php
 * Returns today's timetable for the current user:
 *   teacher → all slots they teach today
 *   student → their class's slots for today
 *   parent  → linked child's class slots (uses ?student_id= if multiple children)
 *   admin/principal → requires ?class_id=
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();

$user = current_user();
$pdo  = db();
$dow  = (int) date('N'); // 1=Mon..7=Sun

if ($dow > 5) { json_ok(['slots' => [], 'note' => 'Weekend']); }

// Get current academic year
$ay = $pdo->query('SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1')->fetchColumn();
if (!$ay) { json_fail('No current academic year set.', 500); }
$ay = (int) $ay;

$baseQuery = '
    SELECT ts.day_of_week, ts.period_id,
           p.period_number, p.label AS period_label, p.start_time, p.end_time, p.is_break,
           ts.class_subject_id,
           c.name AS class_name, c.section,
           s.name AS subject_name,
           u.name AS teacher_name
    FROM   timetable_slots ts
    JOIN   periods       p  ON p.id  = ts.period_id
    JOIN   classes       c  ON c.id  = ts.class_id
    LEFT JOIN class_subjects cs ON cs.id = ts.class_subject_id
    LEFT JOIN subjects   s  ON s.id  = cs.subject_id
    LEFT JOIN users      u  ON u.id  = cs.teacher_id
    WHERE  ts.day_of_week = ? AND ts.academic_year_id = ?
';

switch ($user['role']) {
    case 'teacher':
        $st = $pdo->prepare($baseQuery . ' AND cs.teacher_id = ? ORDER BY p.period_number');
        $st->execute([$dow, $ay, $user['id']]);
        break;

    case 'student':
        $row = $pdo->prepare('SELECT class_id FROM students WHERE user_id = ? LIMIT 1');
        $row->execute([$user['id']]);
        $classId = $row->fetchColumn();
        if (!$classId) { json_ok(['slots' => []]); }
        $st = $pdo->prepare($baseQuery . ' AND ts.class_id = ? ORDER BY p.period_number');
        $st->execute([$dow, $ay, (int)$classId]);
        break;

    case 'parent':
        // Get student_id from query param or default to first linked child
        $studentId = (int) ($_GET['student_id'] ?? 0);
        if ($studentId) {
            // Verify this child belongs to parent
            $check = $pdo->prepare('
                SELECT s.class_id FROM students s
                JOIN parent_student ps ON ps.student_id = s.id
                JOIN parents p ON p.id = ps.parent_id
                WHERE p.user_id = ? AND s.id = ? LIMIT 1
            ');
            $check->execute([$user['id'], $studentId]);
        } else {
            $check = $pdo->prepare('
                SELECT s.class_id FROM students s
                JOIN parent_student ps ON ps.student_id = s.id
                JOIN parents p ON p.id = ps.parent_id
                WHERE p.user_id = ? LIMIT 1
            ');
            $check->execute([$user['id']]);
        }
        $classId = $check->fetchColumn();
        if (!$classId) { json_ok(['slots' => []]); }
        $st = $pdo->prepare($baseQuery . ' AND ts.class_id = ? ORDER BY p.period_number');
        $st->execute([$dow, $ay, (int)$classId]);
        break;

    case 'admin':
    case 'principal':
        $classId = (int) ($_GET['class_id'] ?? 0);
        if (!$classId) { json_fail('class_id required for admin/principal.', 400); }
        $st = $pdo->prepare($baseQuery . ' AND ts.class_id = ? ORDER BY p.period_number');
        $st->execute([$dow, $ay, $classId]);
        break;

    default:
        json_fail('Forbidden.', 403);
}

$slots = $st->fetchAll();

// Determine next class (first non-break slot after current time)
$now  = date('H:i:s');
$next = null;
foreach ($slots as $slot) {
    if (!$slot['is_break'] && $slot['start_time'] > $now) {
        $next = $slot;
        break;
    }
}

json_ok(['slots' => $slots, 'next_class' => $next, 'day_of_week' => $dow]);
