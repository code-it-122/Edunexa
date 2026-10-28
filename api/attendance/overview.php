<?php
/**
 * CASPA School OS – api/attendance/overview.php
 *
 * GET ?date=YYYY-MM-DD (default today). principal + admin only.
 * One row per class+subject that has a teacher, with the status counts for that date
 * and whether attendance has been marked. Also returns totals for the day.
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
require_role(['principal', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_fail('Method not allowed.', 405);
}

$date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
$dt   = DateTime::createFromFormat('Y-m-d', $date);
if (!$dt || $dt->format('Y-m-d') !== $date) {
    json_fail('A valid date in YYYY-MM-DD format is required.', 422);
}
if ($date > date('Y-m-d')) {
    json_fail('Attendance overview is not available for future dates.', 422);
}

$pdo = db();
$st  = $pdo->prepare("
    SELECT cs.id AS class_subject_id,
           c.name AS class_name, c.section AS class_section,
           s.name AS subject_name,
           u.name AS teacher_name,
           COUNT(a.id) AS records,
           COALESCE(SUM(a.status = 'present'), 0) AS present,
           COALESCE(SUM(a.status = 'absent'),  0) AS absent,
           COALESCE(SUM(a.status = 'late'),    0) AS late,
           COALESCE(SUM(a.status = 'excused'), 0) AS excused
    FROM class_subjects cs
    JOIN classes  c ON c.id = cs.class_id
    JOIN subjects s ON s.id = cs.subject_id
    JOIN users    u ON u.id = cs.teacher_id
    LEFT JOIN attendance a ON a.class_subject_id = cs.id AND a.date = :d
    GROUP BY cs.id, c.name, c.section, s.name, u.name
    ORDER BY c.name, c.section, s.name
");
$st->execute([':d' => $date]);
$rows = $st->fetchAll();

$totals = ['records' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'marked' => 0, 'pending' => 0];
foreach ($rows as &$r) {
    foreach (['records', 'present', 'absent', 'late', 'excused'] as $k) {
        $r[$k] = (int) $r[$k];
        $totals[$k] += $r[$k];
    }
    $r['marked'] = $r['records'] > 0;
    $r['rate']   = $r['records'] > 0 ? round(($r['present'] / $r['records']) * 100, 1) : null;
    $totals[$r['marked'] ? 'marked' : 'pending']++;
}
unset($r);
$totals['rate'] = $totals['records'] > 0 ? round(($totals['present'] / $totals['records']) * 100, 1) : null;

json_ok(['date' => $date, 'periods' => $rows, 'totals' => $totals]);
