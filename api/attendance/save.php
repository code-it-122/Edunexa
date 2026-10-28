<?php
/**
 * CASPA School OS – api/attendance/save.php
 *
 * POST {class_subject_id, date, records: [{student_id, status}]}
 * Only the assigned teacher or admin.
 * Validate:
 *   - class_subject exists
 *   - every student_id belongs to that class
 *   - status is a valid ENUM value ('present','absent','late','excused')
 *   - date is a valid date and not in the future
 * Uses INSERT ... ON DUPLICATE KEY UPDATE inside a transaction.
 * Records who marked it (recorded_by).
 * Returns the counts saved.
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
require_role(['teacher', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Method not allowed.', 405);
}

$user = current_user();
$pdo  = db();

// Handle JSON input or form-encoded input
$input = $_POST;
$rawBody = file_get_contents('php://input');
if ($rawBody) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $input = array_merge($input, $decoded);
    }
}

$csId    = isset($input['class_subject_id']) ? (int) $input['class_subject_id'] : 0;
$date    = trim((string) ($input['date'] ?? ''));
$records = $input['records'] ?? [];

// 1. Basic validation
if ($csId <= 0) {
    json_fail('A valid class_subject_id is required.', 422);
}

if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_fail('A valid date in YYYY-MM-DD format is required.', 422);
}

// Check future date
$today = date('Y-m-d');
if ($date > $today) {
    json_fail('Attendance cannot be marked for future dates.', 422);
}

if (!is_array($records) || empty($records)) {
    json_fail('No attendance records provided to save.', 422);
}

// 2. Fetch class_subject and check permission
$csSql = "
    SELECT cs.id, cs.class_id, cs.teacher_id,
           c.name as class_name, c.section as class_section,
           s.name as subject_name
    FROM class_subjects cs
    JOIN classes c ON c.id = cs.class_id
    JOIN subjects s ON s.id = cs.subject_id
    WHERE cs.id = :cs_id
    LIMIT 1
";
$csSt = $pdo->prepare($csSql);
$csSt->execute([':cs_id' => $csId]);
$cs = $csSt->fetch();

if (!$cs) {
    json_fail('Class subject assignment not found.', 404);
}

$isAssignedTeacher = ($user['role'] === 'teacher' && (int) $cs['teacher_id'] === (int) $user['id']);
$isAdmin           = ($user['role'] === 'admin');

if (!$isAssignedTeacher && !$isAdmin) {
    json_fail('Forbidden: you are not assigned to record attendance for this class subject.', 403);
}

$classId = (int) $cs['class_id'];

// 3. Fetch all valid student IDs in this class
$validStudentsSql = "
    SELECT id FROM students WHERE class_id = :class_id
";
$validSt = $pdo->prepare($validStudentsSql);
$validSt->execute([':class_id' => $classId]);
$validStudentIds = array_map('intval', $validSt->fetchAll(PDO::FETCH_COLUMN));
$validSet = array_flip($validStudentIds);

// Allowed status values per schema.sql
$allowedStatuses = ['present', 'absent', 'late', 'excused'];

// Validate all records before starting transaction
$sanitizedRecords = [];
$counts = [
    'present' => 0,
    'absent'  => 0,
    'late'    => 0,
    'excused' => 0,
];

foreach ($records as $index => $rec) {
    if (!is_array($rec)) {
        json_fail("Invalid record format at index {$index}.", 422);
    }

    $sid = isset($rec['student_id']) ? (int) $rec['student_id'] : 0;
    $status = strtolower(trim((string) ($rec['status'] ?? '')));

    if ($sid <= 0 || !isset($validSet[$sid])) {
        json_fail("Student ID {$sid} does not belong to {$cs['class_name']} - Section {$cs['class_section']}.", 422);
    }

    if (!in_array($status, $allowedStatuses, true)) {
        json_fail("Invalid status '{$status}' for student ID {$sid}. Must be present, absent, late, or excused.", 422);
    }

    $sanitizedRecords[$sid] = $status; // deduplicates if duplicate student_id in payload
    $counts[$status]++;
}

// 4. Save with transaction and INSERT ... ON DUPLICATE KEY UPDATE
$pdo->beginTransaction();

try {
    $insertSql = "
        INSERT INTO attendance (student_id, class_subject_id, date, status, recorded_by)
        VALUES (:student_id, :class_subject_id, :att_date, :status, :recorded_by)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            recorded_by = VALUES(recorded_by)
    ";
    $insertSt = $pdo->prepare($insertSql);

    $savedCount = 0;
    foreach ($sanitizedRecords as $sid => $status) {
        $insertSt->execute([
            ':student_id'        => $sid,
            ':class_subject_id'  => $csId,
            ':att_date'          => $date,
            ':status'            => $status,
            ':recorded_by'       => (int) $user['id'],
        ]);
        $savedCount++;
    }

    $pdo->commit();

    json_ok([
        'message'          => "Attendance saved for {$savedCount} student(s).",
        'saved_count'      => $savedCount,
        'class_subject_id' => $csId,
        'date'             => $date,
        'counts'           => $counts,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    json_fail('Database error: failed to save attendance records.', 500);
}
