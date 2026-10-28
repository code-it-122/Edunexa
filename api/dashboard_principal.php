<?php
/**
 * CASPA School OS – api/dashboard_principal.php
 *
 * GET (principal + admin only). School-wide overview, real data only:
 *   kpis                 total students / teachers / classes, today's attendance %,
 *                        assignments created in the last 7 days
 *   attendance_trend     last 7 days (oldest first): records, present, rate
 *   pending_attendance   class+subject periods with a teacher but nothing marked today
 *   announcements        latest 5 published announcements
 *
 * Attendance rate = present records / all records (same definition as api/student.php).
 * Attendance is stored per class_subject per date, so "pending" is per class+subject.
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
require_role(['principal', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_fail('Method not allowed.', 405);
}

$pdo   = db();
$today = date('Y-m-d');

// ── 1. Headcounts ────────────────────────────────────────────────────────────
$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTeachers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_active = 1")->fetchColumn();
$totalClasses  = (int) $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();

// ── 2. Attendance for the last 7 days (including today) ─────────────────────
$from = date('Y-m-d', strtotime('-6 days'));
$st = $pdo->prepare("
    SELECT date,
           COUNT(*) AS records,
           SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present
    FROM attendance
    WHERE date BETWEEN :from AND :to
    GROUP BY date
");
$st->execute([':from' => $from, ':to' => $today]);
$byDate = [];
foreach ($st->fetchAll() as $r) {
    $byDate[$r['date']] = ['records' => (int) $r['records'], 'present' => (int) $r['present']];
}

$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $d       = date('Y-m-d', strtotime("-{$i} days"));
    $records = $byDate[$d]['records'] ?? 0;
    $present = $byDate[$d]['present'] ?? 0;
    $trend[] = [
        'date'    => $d,
        'records' => $records,
        'present' => $present,
        'rate'    => $records > 0 ? round(($present / $records) * 100, 1) : null, // null = nothing marked
    ];
}
$todayRow = $trend[6];

// ── 3. Periods (class+subject) with a teacher but no attendance today ───────
$st = $pdo->prepare("
    SELECT cs.id AS class_subject_id,
           c.name AS class_name, c.section AS class_section,
           s.name AS subject_name,
           u.name AS teacher_name
    FROM class_subjects cs
    JOIN classes  c ON c.id = cs.class_id
    JOIN subjects s ON s.id = cs.subject_id
    JOIN users    u ON u.id = cs.teacher_id
    WHERE NOT EXISTS (
        SELECT 1 FROM attendance a
        WHERE a.class_subject_id = cs.id AND a.date = :today
    )
    ORDER BY c.name, c.section, s.name
");
$st->execute([':today' => $today]);
$pending = $st->fetchAll();

$totalPeriods = (int) $pdo->query("SELECT COUNT(*) FROM class_subjects WHERE teacher_id IS NOT NULL")->fetchColumn();

// ── 4. Assignments created in the last 7 days ───────────────────────────────
$st = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE created_at >= :since");
$st->execute([':since' => $from . ' 00:00:00']);
$assignmentsThisWeek = (int) $st->fetchColumn();

// ── 5. Latest announcements ─────────────────────────────────────────────────
$announcements = $pdo->query("
    SELECT a.id, a.title, a.body, a.audience, a.published_at, u.name AS author_name
    FROM announcements a
    JOIN users u ON u.id = a.created_by
    WHERE a.published_at IS NOT NULL AND a.published_at <= NOW()
      AND (a.expires_at IS NULL OR a.expires_at >= NOW())
    ORDER BY a.published_at DESC, a.id DESC
    LIMIT 5
")->fetchAll();
foreach ($announcements as &$a) {
    $a['audience'] = $a['audience'] === '' ? [] : explode(',', (string) $a['audience']);
}
unset($a);

json_ok([
    'today' => $today,
    'kpis'  => [
        'total_students'          => $totalStudents,
        'total_teachers'          => $totalTeachers,
        'total_classes'           => $totalClasses,
        'today_attendance_rate'   => $todayRow['rate'],          // null if nothing marked yet
        'today_records'           => $todayRow['records'],
        'periods_total'           => $totalPeriods,
        'periods_pending_today'   => count($pending),
        'assignments_this_week'   => $assignmentsThisWeek,
    ],
    'attendance_trend'   => $trend,
    'pending_attendance' => $pending,
    'announcements'      => $announcements,
]);
