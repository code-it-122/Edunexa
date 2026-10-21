<?php
/**
 * CASPA School OS – api/classes.php
 *
 * GET: Returns classes the user may access (via allowed_class_ids).
 *      Includes student count, academic year, class teacher name.
 * POST (admin only): Creates a new class.
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

// ── GET: List classes ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $allowed = allowed_class_ids($user);
    if (empty($allowed)) {
        json_ok([
            'classes'        => [],
            'academic_years' => [],
            'teachers'       => [],
            'can_create'     => ($user['role'] === 'admin'),
        ]);
    }

    $placeholders = implode(',', array_fill(0, count($allowed), '?'));
    $sql = "
        SELECT c.id, c.name, c.section, c.academic_year_id, ay.label as academic_year,
               c.class_teacher_id, u.name as class_teacher_name,
               COUNT(DISTINCT s.id) as student_count
        FROM classes c
        JOIN academic_years ay ON ay.id = c.academic_year_id
        LEFT JOIN users u ON u.id = c.class_teacher_id
        LEFT JOIN students s ON s.class_id = c.id
        WHERE c.id IN ($placeholders)
        GROUP BY c.id
        ORDER BY c.name, c.section
    ";
    $st = $pdo->prepare($sql);
    $st->execute($allowed);
    $classes = $st->fetchAll();

    // Dropdown helpers for admin
    $academicYears = [];
    $teachers = [];
    if ($user['role'] === 'admin') {
        $academicYears = $pdo->query("SELECT id, label, is_current FROM academic_years ORDER BY is_current DESC, id DESC")->fetchAll();
        $teachers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'teacher' AND is_active = 1 ORDER BY name ASC")->fetchAll();
    }

    json_ok([
        'classes'        => $classes,
        'academic_years' => $academicYears,
        'teachers'       => $teachers,
        'can_create'     => ($user['role'] === 'admin'),
    ]);
}

// ── POST: Create class (Admin only) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('admin');

    $fields = json_validate([
        'name'             => 'required|min:2|max:80',
        'section'          => 'required|max:10',
        'academic_year_id' => 'required|int',
    ]);

    $name      = trim($fields['name']);
    $section   = strtoupper(trim($fields['section']));
    $ayId      = (int) $fields['academic_year_id'];
    $teacherId = !empty($_POST['class_teacher_id']) ? (int) $_POST['class_teacher_id'] : null;

    // Verify academic year exists
    $st = $pdo->prepare("SELECT id FROM academic_years WHERE id = :id");
    $st->execute([':id' => $ayId]);
    if (!$st->fetch()) {
        json_fail('Selected academic year does not exist.', 422);
    }

    // If teacher provided, verify teacher exists and is teacher role
    if ($teacherId !== null) {
        $st = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'teacher' AND is_active = 1");
        $st->execute([':id' => $teacherId]);
        if (!$st->fetch()) {
            json_fail('Selected teacher does not exist or is inactive.', 422);
        }
    }

    // Check unique class_year_name_section
    $st = $pdo->prepare("SELECT id FROM classes WHERE academic_year_id = :ay AND name = :name AND section = :sec");
    $st->execute([':ay' => $ayId, ':name' => $name, ':sec' => $section]);
    if ($st->fetch()) {
        json_fail("A class named '{$name} - Section {$section}' already exists for this academic year.", 422);
    }

    // Insert class
    $st = $pdo->prepare("
        INSERT INTO classes (academic_year_id, name, section, class_teacher_id)
        VALUES (:ay, :name, :sec, :teacher)
    ");
    $st->execute([
        ':ay'      => $ayId,
        ':name'    => $name,
        ':sec'     => $section,
        ':teacher' => $teacherId,
    ]);
    $newId = (int) $pdo->lastInsertId();

    json_ok([
        'id'      => $newId,
        'name'    => $name,
        'section' => $section,
        'message' => "Class '{$name} - Section {$section}' created successfully.",
    ], 201);
}

json_fail('Method not allowed.', 405);
