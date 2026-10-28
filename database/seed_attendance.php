<?php
/**
 * CASPA School OS – OPTIONAL demo attendance
 * -----------------------------------------------
 * Fills the last 14 weekdays with realistic, varied attendance so the dashboards,
 * charts and attendance pages have something to show during a demo.
 *
 *   ~90% present, ~5% late, ~4% absent, ~1% excused (fixed random seed, so every
 *   run produces the same numbers).
 *
 * Run AFTER database/seed.php:
 *   CLI:     php database/seed_attendance.php
 *   Browser: http://localhost/Edunexa/database/seed_attendance.php
 *
 * Safe to run more than once: it overwrites the same (student, subject, date) rows.
 * Only class+subject pairs that have a teacher get attendance, and each row is
 * recorded by that teacher. This is FAKE DEMO DATA. Do not run it on real data.
 *
 * GUARD: only runs from the command line or from localhost.
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        die('403 Forbidden - seed_attendance.php may only run from localhost or the CLI.');
    }
}

define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';

$pdo = db();

function out(string $msg): void
{
    global $isCli;
    echo $isCli ? $msg . PHP_EOL : "<p style='font-family:monospace;margin:2px 0'>" . htmlspecialchars($msg) . '</p>';
}

mt_srand(2026);   // same demo numbers every time

// Last 14 weekdays up to and including today
$dates = [];
$day   = new DateTime('today');
while (count($dates) < 14) {
    if ((int) $day->format('N') <= 5) {            // Mon-Fri
        $dates[] = $day->format('Y-m-d');
    }
    $day->modify('-1 day');
}
$dates = array_reverse($dates);

$periods = $pdo->query("
    SELECT cs.id, cs.class_id, cs.teacher_id
    FROM class_subjects cs
    WHERE cs.teacher_id IS NOT NULL
")->fetchAll();

if (!$periods) {
    out('No class+subject assignments with a teacher found. Run database/seed.php first.');
    exit(1);
}

$studentsByClass = [];
foreach ($pdo->query('SELECT id, class_id FROM students WHERE class_id IS NOT NULL')->fetchAll() as $s) {
    $studentsByClass[(int) $s['class_id']][] = (int) $s['id'];
}

$ins = $pdo->prepare("
    INSERT INTO attendance (student_id, class_subject_id, date, status, recorded_by)
    VALUES (:sid, :cs, :d, :st, :by)
    ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_by = VALUES(recorded_by)
");

$pdo->beginTransaction();
$count = 0;
foreach ($periods as $p) {
    foreach ($studentsByClass[(int) $p['class_id']] ?? [] as $sid) {
        foreach ($dates as $d) {
            $r = mt_rand(1, 100);
            $status = $r <= 90 ? 'present' : ($r <= 95 ? 'late' : ($r <= 99 ? 'absent' : 'excused'));
            $ins->execute([
                ':sid' => $sid,
                ':cs'  => (int) $p['id'],
                ':d'   => $d,
                ':st'  => $status,
                ':by'  => (int) $p['teacher_id'],
            ]);
            $count++;
        }
    }
}
$pdo->commit();

out("Demo attendance written: {$count} rows across " . count($periods) . ' class+subject periods and ' . count($dates) . ' weekdays (' . $dates[0] . ' to ' . end($dates) . ').');
out('These rows are fake demo data.');
