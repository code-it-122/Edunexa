<?php
/**
 * CASPA School OS – api/students.php
 *
 * GET ?class_id=&q=&page=
 *   Scoped by allowed_class_ids($user).
 *   Paginated (10 per page).
 *
 * POST (admin only):
 *   Create student user + student profile record.
 */

declare(strict_types=1);

header('Content-Type: application/json');

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

session_boot();
require_login();

$user = current_user();
$pdo  = db();

// ── GET: Paginated students search/filter ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $allowed = allowed_class_ids($user);
    if (empty($allowed)) {
        json_ok([
            'students'    => [],
            'total'       => 0,
            'page'        => 1,
            'per_page'    => 10,
            'total_pages' => 0,
            'can_create'  => ($user['role'] === 'admin'),
        ]);
    }

    $classId = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : null;
    $q       = trim((string) ($_GET['q'] ?? ''));
    $page    = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 10;
    $offset  = ($page - 1) * $perPage;

    // Check specific class scope
    if ($classId !== null) {
        if (!in_array($classId, $allowed, true)) {
            json_fail('Forbidden: you do not have permission to view this class.', 403);
        }
        $targetClassIds = [$classId];
    } else {
        $targetClassIds = $allowed;
    }

    // Build WHERE conditions
    $conditions = [];
    $params     = [];

    // Class scoping
    $placeholders = implode(',', array_fill(0, count($targetClassIds), '?'));
    $conditions[] = "s.class_id IN ($placeholders)";
    foreach ($targetClassIds as $cid) {
        $params[] = $cid;
    }

    // Search query
    if ($q !== '') {
        $conditions[] = "(u.name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ?)";
        $searchTerm   = '%' . $q . '%';
        $params[]     = $searchTerm;
        $params[]     = $searchTerm;
        $params[]     = $searchTerm;
    }

    $whereSql = implode(' AND ', $conditions);

    // Count total
    $countSql = "
        SELECT COUNT(DISTINCT s.id)
        FROM students s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN classes c ON c.id = s.class_id
        WHERE {$whereSql}
    ";
    $countSt = $pdo->prepare($countSql);
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $totalPages = (int) ceil($total / $perPage);

    // Fetch page records
    $dataSql = "
        SELECT s.id, s.user_id, s.class_id, s.admission_number, s.date_of_birth, s.gender, s.address,
               u.name, u.email, u.is_active,
               c.name as class_name, c.section as class_section
        FROM students s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN classes c ON c.id = s.class_id
        WHERE {$whereSql}
        ORDER BY c.name ASC, c.section ASC, s.admission_number ASC
        LIMIT {$perPage} OFFSET {$offset}
    ";
    $dataSt = $pdo->prepare($dataSql);
    $dataSt->execute($params);
    $students = $dataSt->fetchAll();

    json_ok([
        'students'    => $students,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
        'can_create'  => ($user['role'] === 'admin'),
    ]);
}

// ── POST: Create student (Admin only) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('admin');

    $fields = validate_input([
        'name'             => 'required|min:2|max:120',
        'email'            => 'required|email|max:180',
        'admission_number' => 'required|min:2|max:40',
        'class_id'         => 'required|int',
    ]);

    $name    = trim($fields['name']);
    $email   = strtolower(trim($fields['email']));
    $admNo   = strtoupper(trim($fields['admission_number']));
    $classId = (int) $fields['class_id'];

    $gender  = !empty($_POST['gender']) && in_array($_POST['gender'], ['male', 'female', 'other'], true)
        ? $_POST['gender']
        : null;
    $dob     = !empty($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : null;

    // Check unique email
    $st = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $st->execute([':email' => $email]);
    if ($st->fetch()) {
        json_fail('A user with this email address already exists.', 422);
    }

    // Check unique admission_number
    $st = $pdo->prepare("SELECT id FROM students WHERE admission_number = :adm");
    $st->execute([':adm' => $admNo]);
    if ($st->fetch()) {
        json_fail("A student with admission number '{$admNo}' already exists.", 422);
    }

    // Check class exists
    $st = $pdo->prepare("SELECT id, name, section FROM classes WHERE id = :cid");
    $st->execute([':cid' => $classId]);
    $classRow = $st->fetch();
    if (!$classRow) {
        json_fail('Selected class does not exist.', 422);
    }

    // Validate date format if provided
    if ($dob !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        json_fail('Date of birth must be in YYYY-MM-DD format.', 422);
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Default student password is 'demo123'
        $hash = password_hash('demo123', PASSWORD_BCRYPT);
        $userSt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, is_active)
            VALUES (:name, :email, :hash, 'student', 1)
        ");
        $userSt->execute([
            ':name'  => $name,
            ':email' => $email,
            ':hash'  => $hash,
        ]);
        $newUserId = (int) $pdo->lastInsertId();

        $studentSt = $pdo->prepare("
            INSERT INTO students (user_id, class_id, admission_number, date_of_birth, gender, address)
            VALUES (:uid, :cid, :adm, :dob, :gender, :addr)
        ");
        $studentSt->execute([
            ':uid'    => $newUserId,
            ':cid'    => $classId,
            ':adm'    => $admNo,
            ':dob'    => $dob,
            ':gender' => $gender,
            ':addr'   => $address,
        ]);
        $newStudentId = (int) $pdo->lastInsertId();

        $pdo->commit();

        json_ok([
            'id'               => $newStudentId,
            'user_id'          => $newUserId,
            'name'             => $name,
            'email'            => $email,
            'admission_number' => $admNo,
            'class_id'         => $classId,
            'class_label'      => $classRow['name'] . ' - ' . $classRow['section'],
            'message'          => "Student '{$name}' created successfully.",
        ], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_fail('Database error: failed to create student.', 500);
    }
}

json_fail('Method not allowed.', 405);
