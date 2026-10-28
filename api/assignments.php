<?php
/**
 * CASPA School OS – api/assignments.php
 *
 * GET ?class_id=:
 *   Role-scoped list of assignments:
 *   - teacher: classes/class_subjects they teach (or all allowed classes)
 *   - student: assignments for their enrolled class
 *   - parent: assignments for their linked children's classes
 *   - principal / admin: all classes
 *
 * POST (teacher only):
 *   Create assignment for an assigned class_subject.
 *   Validate title (required, max 200), due_date (required, not in the past).
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

$user = current_user();
$pdo  = db();

// ── GET: List assignments scoped by role ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $allowedClassIds = allowed_class_ids($user);

    if (empty($allowedClassIds)) {
        json_ok([
            'assignments'        => [],
            'my_class_subjects'  => [],
            'can_create'         => ($user['role'] === 'teacher'),
        ]);
    }

    $classIdFilter = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : null;

    if ($classIdFilter !== null) {
        if (!in_array($classIdFilter, $allowedClassIds, true)) {
            json_fail('Forbidden: you do not have permission to view assignments for this class.', 403);
        }
        $targetClassIds = [$classIdFilter];
    } else {
        $targetClassIds = $allowedClassIds;
    }

    $placeholders = implode(',', array_fill(0, count($targetClassIds), '?'));
    $params = $targetClassIds;

    // For teachers: filter by class_subjects they teach (or are assigned to)
    $teacherExtraWhere = '';
    if ($user['role'] === 'teacher') {
        $teacherExtraWhere = "AND (cs.teacher_id = ? OR a.created_by = ?)";
        $params[] = (int) $user['id'];
        $params[] = (int) $user['id'];
    }

    $sql = "
        SELECT a.id, a.class_subject_id, a.title, a.description, a.due_date,
               a.max_marks, a.created_by, a.created_at,
               cs.class_id, cs.subject_id,
               c.name as class_name, c.section as class_section,
               s.name as subject_name, s.code as subject_code,
               u.name as creator_name
        FROM assignments a
        JOIN class_subjects cs ON cs.id = a.class_subject_id
        JOIN classes c ON c.id = cs.class_id
        JOIN subjects s ON s.id = cs.subject_id
        JOIN users u ON u.id = a.created_by
        WHERE cs.class_id IN ($placeholders)
        {$teacherExtraWhere}
        ORDER BY a.due_date ASC, a.id DESC
    ";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $assignments = $st->fetchAll();

    // If teacher, also provide their assigned class_subjects for the create modal dropdown
    $myClassSubjects = [];
    if ($user['role'] === 'teacher') {
        $csSql = "
            SELECT cs.id as class_subject_id, cs.class_id, cs.subject_id,
                   c.name as class_name, c.section as class_section,
                   s.name as subject_name, s.code as subject_code
            FROM class_subjects cs
            JOIN classes c ON c.id = cs.class_id
            JOIN subjects s ON s.id = cs.subject_id
            WHERE cs.teacher_id = :tid
            ORDER BY c.name ASC, c.section ASC, s.name ASC
        ";
        $csSt = $pdo->prepare($csSql);
        $csSt->execute([':tid' => (int) $user['id']]);
        $myClassSubjects = $csSt->fetchAll();
    }

    json_ok([
        'assignments'       => $assignments,
        'my_class_subjects' => $myClassSubjects,
        'can_create'        => ($user['role'] === 'teacher'),
        'current_user_id'   => (int) $user['id'],
    ]);
}

// ── POST: Create assignment (Teacher only) ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('teacher');

    // Accept form-encoded or JSON payload
    $input = $_POST;
    $rawBody = file_get_contents('php://input');
    if ($rawBody) {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $input = array_merge($input, $decoded);
        }
    }

    $title       = trim((string) ($input['title'] ?? ''));
    $csId        = isset($input['class_subject_id']) ? (int) $input['class_subject_id'] : 0;
    $dueDate     = trim((string) ($input['due_date'] ?? ''));
    $description = isset($input['description']) ? trim((string) $input['description']) : null;
    $maxMarks    = isset($input['max_marks']) && is_numeric($input['max_marks']) ? (float) $input['max_marks'] : 100.0;

    // Validate title
    if ($title === '') {
        json_fail('Assignment title is required.', 422);
    }
    if (mb_strlen($title) > 200) {
        json_fail('Assignment title must be at most 200 characters.', 422);
    }

    // Validate class_subject_id
    if ($csId <= 0) {
        json_fail('A valid class and subject assignment is required.', 422);
    }

    // Validate due_date
    if ($dueDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        json_fail('Due date is required and must be in YYYY-MM-DD format.', 422);
    }

    $today = date('Y-m-d');
    if ($dueDate < $today) {
        json_fail('Due date cannot be in the past.', 422);
    }

    // Verify teacher is assigned to this class_subject
    $checkSql = "
        SELECT cs.id, cs.class_id, cs.subject_id, cs.teacher_id,
               c.name as class_name, c.section as class_section,
               s.name as subject_name
        FROM class_subjects cs
        JOIN classes c ON c.id = cs.class_id
        JOIN subjects s ON s.id = cs.subject_id
        WHERE cs.id = :cs_id
        LIMIT 1
    ";
    $csSt = $pdo->prepare($checkSql);
    $csSt->execute([':cs_id' => $csId]);
    $cs = $csSt->fetch();

    if (!$cs) {
        json_fail('Class subject assignment not found.', 404);
    }

    if ((int) $cs['teacher_id'] !== (int) $user['id']) {
        json_fail('Forbidden: you are not assigned to teach this class subject.', 403);
    }

    // Insert assignment
    $insertSql = "
        INSERT INTO assignments (class_subject_id, title, description, due_date, max_marks, created_by)
        VALUES (:cs_id, :title, :description, :due_date, :max_marks, :created_by)
    ";
    $st = $pdo->prepare($insertSql);
    $st->execute([
        ':cs_id'       => $csId,
        ':title'       => $title,
        ':description' => $description,
        ':due_date'    => $dueDate,
        ':max_marks'   => $maxMarks,
        ':created_by'  => (int) $user['id'],
    ]);

    $newId = (int) $pdo->lastInsertId();

    json_ok([
        'id'               => $newId,
        'title'            => $title,
        'due_date'         => $dueDate,
        'class_subject_id' => $csId,
        'class_name'       => $cs['class_name'] . ' - ' . $cs['class_section'],
        'subject_name'     => $cs['subject_name'],
        'message'          => "Assignment '{$title}' created successfully.",
    ], 201);
}

json_fail('Method not allowed.', 405);
