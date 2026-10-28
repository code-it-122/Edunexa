<?php
/**
 * CASPA School OS – api/dashboard_family.php
 *
 * GET [?student_id=]   student + parent only.
 *   student → always their own record
 *   parent  → one of their linked children (defaults to the first); any other id → 403
 *
 * Returns:
 *   children      the children this user may switch between
 *   selected      the chosen child (name, class, admission no.)
 *   attendance    summary (rate = present / all records) + the 10 most recent records
 *   assignments   upcoming assignments (next 5) for the child's class, plus overdue count
 *   announcements latest 3 published announcements targeted at the user's role
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
require_role(['student', 'parent']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_fail('Method not allowed.', 405);
}

$user  = current_user();
$pdo   = db();
$today = date('Y-m-d');

// ── 1. Which children may this user see? (server-side, never trust the client) ──
$allowedIds = restricted_student_ids($user) ?? [];

$children = [];
if ($allowedIds) {
    $in = implode(',', array_fill(0, count($allowedIds), '?'));
    $st = $pdo->prepare("
        SELECT s.id, s.class_id, s.admission_number, u.name,
               c.name AS class_name, c.section AS class_section
        FROM students s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN classes c ON c.id = s.class_id
        WHERE s.id IN ($in)
        ORDER BY u.name ASC
    ");
    $st->execute($allowedIds);
    $children = $st->fetchAll();
}

$announcements = (function () use ($pdo, $user) {
    $st = $pdo->prepare("
        SELECT a.id, a.title, a.body, a.published_at, u.name AS author_name
        FROM announcements a
        JOIN users u ON u.id = a.created_by
        WHERE FIND_IN_SET(:role, a.audience) > 0
          AND a.published_at IS NOT NULL AND a.published_at <= NOW()
          AND (a.expires_at IS NULL OR a.expires_at >= NOW())
        ORDER BY a.published_at DESC, a.id DESC
        LIMIT 3
    ");
    $st->execute([':role' => $user['role']]);
    return $st->fetchAll();
})();

if (!$children) {
    json_ok([
        'children' => [], 'selected' => null, 'attendance' => null,
        'assignments' => null, 'announcements' => $announcements,
    ]);
}

// ── 2. Pick the child ────────────────────────────────────────────────────────
$requested = isset($_GET['student_id']) && $_GET['student_id'] !== '' ? (int) $_GET['student_id'] : null;
if ($requested !== null && !in_array($requested, $allowedIds, true)) {
    json_fail('Forbidden: you do not have permission to view this student.', 403);
}
$selected = $children[0];
if ($requested !== null) {
    foreach ($children as $c) {
        if ((int) $c['id'] === $requested) { $selected = $c; break; }
    }
}
$sid = (int) $selected['id'];

// ── 3. Attendance ────────────────────────────────────────────────────────────
$st = $pdo->prepare("
    SELECT COUNT(*) AS total,
           COALESCE(SUM(status = 'present'), 0) AS present,
           COALESCE(SUM(status = 'absent'),  0) AS absent,
           COALESCE(SUM(status = 'late'),    0) AS late,
           COALESCE(SUM(status = 'excused'), 0) AS excused
    FROM attendance WHERE student_id = :sid
");
$st->execute([':sid' => $sid]);
$sum = $st->fetch();
$total = (int) $sum['total'];

$st = $pdo->prepare("
    SELECT a.date, a.status, s.name AS subject_name
    FROM attendance a
    JOIN class_subjects cs ON cs.id = a.class_subject_id
    JOIN subjects s ON s.id = cs.subject_id
    WHERE a.student_id = :sid
    ORDER BY a.date DESC, a.id DESC
    LIMIT 10
");
$st->execute([':sid' => $sid]);
$recent = $st->fetchAll();

// ── 4. Assignments for the child's class ────────────────────────────────────
$upcoming = [];
$overdue  = 0;
if ($selected['class_id'] !== null) {
    $st = $pdo->prepare("
        SELECT a.id, a.title, a.due_date, a.max_marks, s.name AS subject_name
        FROM assignments a
        JOIN class_subjects cs ON cs.id = a.class_subject_id
        JOIN subjects s ON s.id = cs.subject_id
        WHERE cs.class_id = :cid AND a.due_date >= :today
        ORDER BY a.due_date ASC, a.id ASC
        LIMIT 5
    ");
    $st->execute([':cid' => (int) $selected['class_id'], ':today' => $today]);
    $upcoming = $st->fetchAll();

    $st = $pdo->prepare("
        SELECT COUNT(*)
        FROM assignments a
        JOIN class_subjects cs ON cs.id = a.class_subject_id
        WHERE cs.class_id = :cid AND a.due_date < :today
    ");
    $st->execute([':cid' => (int) $selected['class_id'], ':today' => $today]);
    $overdue = (int) $st->fetchColumn();
}

json_ok([
    'children' => array_map(fn($c) => [
        'id'   => (int) $c['id'],
        'name' => $c['name'],
        'class_name' => trim(($c['class_name'] ?? '') . ' - ' . ($c['class_section'] ?? ''), ' -'),
    ], $children),
    'selected' => [
        'id'               => $sid,
        'name'             => $selected['name'],
        'admission_number' => $selected['admission_number'],
        'class_name'       => trim(($selected['class_name'] ?? '') . ' - ' . ($selected['class_section'] ?? ''), ' -'),
    ],
    'attendance' => [
        'total'   => $total,
        'present' => (int) $sum['present'],
        'absent'  => (int) $sum['absent'],
        'late'    => (int) $sum['late'],
        'excused' => (int) $sum['excused'],
        'rate'    => $total > 0 ? round(((int) $sum['present'] / $total) * 100, 1) : null,
        'recent'  => $recent,
    ],
    'assignments' => [
        'upcoming'      => $upcoming,
        'overdue_count' => $overdue,
        'today'         => $today,
    ],
    'announcements' => $announcements,
]);
