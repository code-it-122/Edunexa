<?php
/**
 * CASPA School OS – api/attendance/my_class_subjects.php
 *
 * GET (teacher): returns class_subjects rows assigned to the logged-in teacher
 * with class name, section, and subject name.
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
require_role('teacher');

$user = current_user();
$pdo  = db();

$sql = "
    SELECT cs.id as class_subject_id,
           cs.class_id,
           cs.subject_id,
           c.name as class_name,
           c.section as class_section,
           s.name as subject_name,
           s.code as subject_code
    FROM class_subjects cs
    JOIN classes c ON c.id = cs.class_id
    JOIN subjects s ON s.id = cs.subject_id
    WHERE cs.teacher_id = :teacher_id
    ORDER BY c.name ASC, c.section ASC, s.name ASC
";

$st = $pdo->prepare($sql);
$st->execute([':teacher_id' => (int) $user['id']]);
$rows = $st->fetchAll();

json_ok($rows);
