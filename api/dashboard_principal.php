<?php
/**
 * CASPA School OS – api/dashboard_principal.php
 *
 * GET (principal | admin only):
 * Returns school-wide dashboard data:
 *   - KPIs: total students, total teachers, total classes, today's school attendance %
 *   - Per-class attendance for last 7 days (for chart)
 *   - Classes where attendance is NOT yet marked today
 *   - Assignments created this week
 *   - Latest 5 announcements (role-scoped)
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
// Build last-7-days date list (oldest first, today last)
$last7 = [];
for ($i = 6; $i >= 0; $i--) {
    $last7[] = date('Y-m-d', strtotime("-{$i} days"));
}
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd   = date('Y-m-d', strtotime('sunday this week'));

// ── 1. KPIs ────────────────────────────────────────────────────────────────
$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTeachers = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_active = 1"
)->fetchColumn();
$totalClasses  = (int) $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();

// Today's overall attendance %:
// Among all attendance rows for today, what % are 'present' or 'late'?
$todayAtt = $pdo->prepare("
    SELECT
        COUNT(*)                                                     AS total_marked,
        SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) AS present_count
    FROM attendance a
    WHERE a.date = :today
");
$todayAtt->execute([':today' => $today]);
$attRow = $todayAtt->fetch();
$todayAttendancePct = 0;
if ((int)($attRow['total_marked'] ?? 0) > 0) {
    $todayAttendancePct = round(
        ((int)$attRow['present_count'] / (int)$attRow['total_marked']) * 100,
        1
    );
}

// ── 2. All classes list ─────────────────────────────────────────────────────
$classRows = $pdo->query("
    SELECT c.id, c.name, c.section,
           COALESCE(u.name, '—') AS class_teacher_name
    FROM   classes c
    LEFT JOIN users u ON u.id = c.class_teacher_id
    ORDER BY c.name ASC, c.section ASC
")->fetchAll();

// ── 3. Per-class attendance last 7 days ────────────────────────────────────
// Returns one row per (class, date): total students in class, how many marked
$classDayData = $pdo->prepare("
    SELECT
        cs.class_id,
        a.date,
        COUNT(DISTINCT a.student_id)                                         AS marked_count,
        SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END)     AS present_count
    FROM   attendance a
    JOIN   class_subjects cs ON cs.id = a.class_subject_id
    WHERE  a.date BETWEEN :start AND :end
    GROUP  BY cs.class_id, a.date
");
$classDayData->execute([':start' => $last7[0], ':end' => $today]);
$rawClassDay = $classDayData->fetchAll();

// Index: [class_id][date] = [marked, present]
$classDayIndex = [];
foreach ($rawClassDay as $r) {
    $classDayIndex[(int)$r['class_id']][$r['date']] = [
        'marked'  => (int)$r['marked_count'],
        'present' => (int)$r['present_count'],
    ];
}

// Student counts per class
$studentCountRows = $pdo->query("
    SELECT class_id, COUNT(*) AS cnt FROM students WHERE class_id IS NOT NULL GROUP BY class_id
")->fetchAll();
$studentCounts = [];
foreach ($studentCountRows as $r) {
    $studentCounts[(int)$r['class_id']] = (int)$r['cnt'];
}

// Build chart dataset: one series per class, attendance % per day
$classAttendanceTrend = [];
foreach ($classRows as $c) {
    $cid    = (int)$c['id'];
    $series = [];
    foreach ($last7 as $d) {
        $dayData = $classDayIndex[$cid][$d] ?? null;
        if ($dayData !== null && $dayData['marked'] > 0) {
            $pct = round(($dayData['present'] / $dayData['marked']) * 100, 1);
        } else {
            $pct = null; // no data for that day
        }
        $series[] = $pct;
    }
    $classAttendanceTrend[] = [
        'class_id'    => $cid,
        'class_name'  => $c['name'] . ' ' . $c['section'],
        'daily_pcts'  => $series,  // null = no attendance recorded
    ];
}

// ── 4. Classes not yet marked today ────────────────────────────────────────
// A class is "not marked" if no attendance row exists today for any of its class_subjects
$markedClassIds = $pdo->prepare("
    SELECT DISTINCT cs.class_id
    FROM   attendance a
    JOIN   class_subjects cs ON cs.id = a.class_subject_id
    WHERE  a.date = :today
");
$markedClassIds->execute([':today' => $today]);
$markedSet = array_map('intval', $markedClassIds->fetchAll(PDO::FETCH_COLUMN));

$pendingClasses = [];
foreach ($classRows as $c) {
    $cid = (int)$c['id'];
    if (!in_array($cid, $markedSet, true)) {
        $pendingClasses[] = [
            'class_id'           => $cid,
            'class_name'         => $c['name'],
            'class_section'      => $c['section'],
            'display_name'       => $c['name'] . ' ' . $c['section'],
            'class_teacher_name' => $c['class_teacher_name'],
            'student_count'      => $studentCounts[$cid] ?? 0,
        ];
    }
}

// ── 5. Assignments created this week ───────────────────────────────────────
$weeklyAssignments = (int) $pdo->prepare("
    SELECT COUNT(*) FROM assignments
    WHERE created_at >= :start AND created_at <= :end
")->execute([':start' => $weekStart . ' 00:00:00', ':end' => $weekEnd . ' 23:59:59'])
    ? $pdo->query("
        SELECT COUNT(*) FROM assignments
        WHERE DATE(created_at) BETWEEN '{$weekStart}' AND '{$weekEnd}'
    ")->fetchColumn()
    : 0;
// Re-query cleanly:
$waSt = $pdo->prepare("
    SELECT COUNT(*) FROM assignments
    WHERE DATE(created_at) BETWEEN :s AND :e
");
$waSt->execute([':s' => $weekStart, ':e' => $weekEnd]);
$weeklyAssignments = (int)$waSt->fetchColumn();

// ── 6. Latest 5 announcements (all audiences) ──────────────────────────────
$annSt = $pdo->query("
    SELECT a.id, a.title, a.body, a.audience, a.published_at, a.expires_at,
           u.name AS author_name
    FROM   announcements a
    JOIN   users u ON u.id = a.created_by
    WHERE  a.published_at IS NOT NULL
      AND  a.published_at <= NOW()
      AND  (a.expires_at IS NULL OR a.expires_at >= NOW())
    ORDER  BY a.published_at DESC, a.id DESC
    LIMIT  5
");
$announcements = $annSt->fetchAll();

// ── 7. Return ──────────────────────────────────────────────────────────────
json_ok([
    'today'      => $today,
    'date_labels'=> $last7,
    'kpis' => [
        'total_students'          => $totalStudents,
        'total_teachers'          => $totalTeachers,
        'total_classes'           => $totalClasses,
        'today_attendance_pct'    => $todayAttendancePct,
        'pending_classes_count'   => count($pendingClasses),
        'weekly_assignments'      => $weeklyAssignments,
    ],
    'class_attendance_trend' => $classAttendanceTrend,
    'pending_classes'        => $pendingClasses,
    'announcements'          => $announcements,
]);
