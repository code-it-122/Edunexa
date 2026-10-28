<?php
/**
 * CASPA School OS – api/assignment_delete.php
 *
 * POST: Teacher can delete only their own assignment.
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
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Method not allowed.', 405);
}

$user = current_user();
$pdo  = db();

// Accept form-encoded or JSON payload
$input = $_POST;
$rawBody = file_get_contents('php://input');
if ($rawBody) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $input = array_merge($input, $decoded);
    }
}

$assignmentId = isset($input['id']) ? (int) $input['id'] : 0;
if ($assignmentId <= 0) {
    json_fail('A valid assignment ID is required.', 422);
}

// Check assignment existence and ownership
$st = $pdo->prepare("SELECT id, title, created_by FROM assignments WHERE id = :id LIMIT 1");
$st->execute([':id' => $assignmentId]);
$asgn = $st->fetch();

if (!$asgn) {
    json_fail('Assignment not found.', 404);
}

// Teacher can only delete their own assignment
if ((int) $asgn['created_by'] !== (int) $user['id']) {
    json_fail('Forbidden: you can only delete assignments that you created.', 403);
}

// Delete assignment (grades cascade delete via foreign key)
$delSt = $pdo->prepare("DELETE FROM assignments WHERE id = :id");
$delSt->execute([':id' => $assignmentId]);

json_ok([
    'deleted_id' => $assignmentId,
    'message'    => "Assignment '{$asgn['title']}' deleted successfully.",
]);
