<?php
// api/attendance.php - Attendance API Endpoints
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';

// 1. Session & Auth Check
if (!isLoggedIn()) {
    jsonResponse(['ok' => false, 'error' => 'Authentication required. Please log in.'], 401);
}

$user = getCurrentUser();
$roleKey = strtolower($user['active_role']['key'] ?? '');
$userId = (int)$user['id'];

// 2. Role Check: Allow only Teacher, Principal, Admin
$allowedRoles = ['teacher', 'principal', 'admin'];
if (!in_array($roleKey, $allowedRoles, true)) {
    jsonResponse(['ok' => false, 'error' => 'Access denied. Only teachers and administrators can access attendance.'], 403);
}

$pdo = getDbConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

// Helper function to check class authorization
function checkClassAccess($pdo, $classId, $roleKey, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();

    if (!$class) {
        jsonResponse(['ok' => false, 'error' => 'Class not found.'], 404);
    }

    // A Teacher may only access classes assigned to them
    if ($roleKey === 'teacher' && (int)$class['teacher_id'] !== $userId) {
        jsonResponse(['ok' => false, 'error' => 'Access denied. You are not authorized to access this class.'], 403);
    }

    return $class;
}

// -------------------------------------------------------------
// GET: action=classes (Fetch classes accessible by current user)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'classes') {
    if ($roleKey === 'teacher') {
        $stmt = $pdo->prepare("SELECT id, name, section, teacher_id FROM classes WHERE teacher_id = ? ORDER BY name, section");
        $stmt->execute([$userId]);
    } else {
        // Principal/Admin may access all classes
        $stmt = $pdo->query("SELECT id, name, section, teacher_id FROM classes ORDER BY name, section");
    }
    $classes = $stmt->fetchAll();

    jsonResponse(['ok' => true, 'data' => ['classes' => $classes]]);
}

// -------------------------------------------------------------
// GET: action=roster (Fetch student roster & existing attendance)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'roster') {
    $classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    $date = trim($_GET['date'] ?? date('Y-m-d'));

    if (!$classId) {
        jsonResponse(['ok' => false, 'error' => 'Class ID is required.'], 400);
    }

    // Validate date format YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid date format.'], 400);
    }

    // Reject future dates server-side
    $today = date('Y-m-d');
    if ($date > $today) {
        jsonResponse(['ok' => false, 'error' => 'Cannot view or mark attendance for future dates.'], 400);
    }

    // Authorization check
    $class = checkClassAccess($pdo, $classId, $roleKey, $userId);

    // Fetch students with attendance for that date
    $query = "
        SELECT s.id as student_id, s.roll_no, s.full_name, s.gender,
               a.status, a.remark, a.marked_by, a.updated_at
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id AND a.att_date = ?
        WHERE s.class_id = ?
        ORDER BY CAST(s.roll_no AS UNSIGNED), s.roll_no, s.full_name
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$date, $classId]);
    $rawStudents = $stmt->fetchAll();

    $isMarked = false;
    $students = [];
    foreach ($rawStudents as $row) {
        if ($row['status'] !== null) {
            $isMarked = true;
        }
        $students[] = [
            'student_id' => (int)$row['student_id'],
            'roll_no' => htmlspecialchars($row['roll_no']),
            'full_name' => htmlspecialchars($row['full_name']),
            'gender' => $row['gender'] ?? 'male',
            'status' => $row['status'] !== null ? $row['status'] : 'present', // default to 'present' per requirements
            'remark' => $row['remark'] ? htmlspecialchars($row['remark']) : '',
            'is_previously_saved' => ($row['status'] !== null)
        ];
    }

    jsonResponse([
        'ok' => true,
        'data' => [
            'class' => [
                'id' => (int)$class['id'],
                'name' => htmlspecialchars($class['name']),
                'section' => htmlspecialchars($class['section']),
                'display' => htmlspecialchars($class['name'] . '-' . $class['section'])
            ],
            'date' => $date,
            'is_marked' => $isMarked,
            'students' => $students
        ]
    ]);
}

// -------------------------------------------------------------
// POST: action=save (Upsert attendance inside transaction)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    // 1. CSRF Token Check
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($csrfToken) && !empty($input['csrf_token'])) {
        $csrfToken = $input['csrf_token'];
    }

    if (!verifyCSRFToken($csrfToken)) {
        jsonResponse(['ok' => false, 'error' => 'Security validation failed (Invalid CSRF token). Please refresh and try again.'], 403);
    }

    $classId = isset($input['class_id']) ? (int)$input['class_id'] : 0;
    $date = trim($input['date'] ?? '');
    $records = $input['records'] ?? [];

    if (!$classId || empty($date)) {
        jsonResponse(['ok' => false, 'error' => 'Class ID and date are required.'], 400);
    }

    // Validate date format YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid date format.'], 400);
    }

    // Reject future dates server-side
    $today = date('Y-m-d');
    if ($date > $today) {
        jsonResponse(['ok' => false, 'error' => 'Cannot record attendance for future dates.'], 400);
    }

    // Authorization check
    $class = checkClassAccess($pdo, $classId, $roleKey, $userId);

    if (!is_array($records) || empty($records)) {
        jsonResponse(['ok' => false, 'error' => 'No student attendance records provided.'], 400);
    }

    $validStatuses = ['present', 'absent', 'late', 'leave'];

    // Upsert query depending on SQL driver
    if ($driver === 'mysql') {
        $upsertSql = "
            INSERT INTO attendance (student_id, class_id, att_date, status, remark, marked_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                remark = VALUES(remark),
                marked_by = VALUES(marked_by),
                updated_at = NOW()
        ";
    } else {
        // SQLite
        $upsertSql = "
            INSERT INTO attendance (student_id, class_id, att_date, status, remark, marked_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ON CONFLICT(student_id, att_date) DO UPDATE SET
                status = excluded.status,
                remark = excluded.remark,
                marked_by = excluded.marked_by,
                updated_at = datetime('now')
        ";
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare($upsertSql);

        $savedCount = 0;
        foreach ($records as $rec) {
            $studentId = (int)($rec['student_id'] ?? 0);
            $status = strtolower(trim($rec['status'] ?? 'present'));
            $remark = isset($rec['remark']) ? substr(trim($rec['remark']), 0, 255) : null;
            if ($remark === '') $remark = null;

            if (!$studentId) {
                continue;
            }

            // Reject invalid statuses server-side
            if (!in_array($status, $validStatuses, true)) {
                $pdo->rollBack();
                jsonResponse(['ok' => false, 'error' => "Invalid status '{$status}' for student ID {$studentId}."], 400);
            }

            $stmt->execute([$studentId, $classId, $date, $status, $remark, $userId]);
            $savedCount++;
        }

        $pdo->commit();

        $classDisplay = htmlspecialchars($class['name'] . '-' . $class['section']);
        jsonResponse([
            'ok' => true,
            'data' => [
                'message' => "Attendance saved for {$classDisplay}",
                'class' => $classDisplay,
                'date' => $date,
                'count' => $savedCount
            ]
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['ok' => false, 'error' => 'Invalid action or unsupported request method.'], 400);
