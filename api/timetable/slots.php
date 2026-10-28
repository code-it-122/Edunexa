<?php
/**
 * CASPA School OS – /api/timetable/slots.php
 *
 * GET  ?class_id=&academic_year_id=  – fetch timetable for a class
 * POST {action:"save", class_id, academic_year_id, slots:[{period_id,day_of_week,class_subject_id|null}]}
 *      – admin/principal only; replaces all slots for that class+year; clash-detects teacher
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();

$user   = current_user();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* ── GET ─────────────────────────────────────────────────────────────────── */
if ($method === 'GET') {
    $classId = (int) ($_GET['class_id'] ?? 0);
    $ayId    = (int) ($_GET['academic_year_id'] ?? 0);
    if (!$classId || !$ayId) { json_fail('class_id and academic_year_id required.', 400); }

    // All logged-in users may view any class timetable
    $st = db()->prepare('
        SELECT ts.id, ts.day_of_week, ts.period_id,
               p.label AS period_label, p.start_time, p.end_time, p.is_break,
               ts.class_subject_id,
               cs.subject_id, s.name AS subject_name,
               cs.teacher_id, u.name AS teacher_name
        FROM   timetable_slots ts
        JOIN   periods p ON p.id = ts.period_id
        LEFT JOIN class_subjects cs ON cs.id = ts.class_subject_id
        LEFT JOIN subjects s ON s.id = cs.subject_id
        LEFT JOIN users    u ON u.id = cs.teacher_id
        WHERE  ts.class_id = ? AND ts.academic_year_id = ?
        ORDER  BY ts.day_of_week, p.period_number
    ');
    $st->execute([$classId, $ayId]);
    json_ok($st->fetchAll());
}

/* ── POST ────────────────────────────────────────────────────────────────── */
require_role(['admin', 'principal']);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = $body['action']           ?? '';
$classId = (int) ($body['class_id'] ?? 0);
$ayId    = (int) ($body['academic_year_id'] ?? 0);
$slots   = $body['slots'] ?? [];

if ($action !== 'save')         { json_fail('action must be "save".', 400); }
if (!$classId || !$ayId)        { json_fail('class_id and academic_year_id required.', 400); }
if (!is_array($slots))          { json_fail('slots must be an array.', 400); }

// Verify class exists
$chk = db()->prepare('SELECT id FROM classes WHERE id = ?');
$chk->execute([$classId]);
if (!$chk->fetch()) { json_fail('Class not found.', 404); }

// Clash detection: build teacher→[day,period] map from OTHER classes in same year
// then check each incoming slot
$clashCheck = db()->prepare('
    SELECT ts.day_of_week, ts.period_id, cs.teacher_id
    FROM   timetable_slots ts
    JOIN   class_subjects cs ON cs.id = ts.class_subject_id
    WHERE  ts.academic_year_id = ?
      AND  ts.class_id <> ?
      AND  cs.teacher_id IS NOT NULL
');
$clashCheck->execute([$ayId, $classId]);
// map: teacher_id -> set of "day-period"
$teacherSlots = [];
foreach ($clashCheck->fetchAll() as $r) {
    $key = $r['day_of_week'] . '-' . $r['period_id'];
    $teacherSlots[(int)$r['teacher_id']][$key] = true;
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Delete existing slots for this class+year
    $del = $pdo->prepare('DELETE FROM timetable_slots WHERE class_id = ? AND academic_year_id = ?');
    $del->execute([$classId, $ayId]);

    $ins = $pdo->prepare('
        INSERT INTO timetable_slots (class_id, class_subject_id, period_id, day_of_week, academic_year_id)
        VALUES (?, ?, ?, ?, ?)
    ');

    foreach ($slots as $slot) {
        $periodId  = (int) ($slot['period_id']  ?? 0);
        $dow       = (int) ($slot['day_of_week'] ?? 0);
        $csId      = isset($slot['class_subject_id']) && $slot['class_subject_id'] ? (int)$slot['class_subject_id'] : null;

        if (!$periodId || $dow < 1 || $dow > 5) { continue; }

        // Clash detection for teacher
        if ($csId !== null) {
            $tSt = $pdo->prepare('SELECT teacher_id FROM class_subjects WHERE id = ?');
            $tSt->execute([$csId]);
            $row = $tSt->fetch();
            if ($row && $row['teacher_id']) {
                $tid = (int) $row['teacher_id'];
                $key = $dow . '-' . $periodId;
                if (!empty($teacherSlots[$tid][$key])) {
                    $pdo->rollBack();
                    json_fail("Teacher clash detected on day {$dow} period {$periodId}.", 422);
                }
                // Add to map so duplicate within THIS class save is also caught
                $teacherSlots[$tid][$key] = true;
            }
        }

        $ins->execute([$classId, $csId, $periodId, $dow, $ayId]);
    }

    $pdo->commit();
} catch (\Exception $e) {
    $pdo->rollBack();
    json_fail('Save failed: ' . $e->getMessage(), 500);
}

json_ok(['saved' => count($slots)]);
