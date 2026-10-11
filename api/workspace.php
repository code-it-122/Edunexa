<?php
// api/workspace.php - Workspace / Role Switcher API
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required.'], 401);
}

$pdo = getDbConnection();
$action = $_REQUEST['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->prepare("
        SELECT r.id, r.key, r.title, r.description, r.category
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $workspaces = $stmt->fetchAll();

    // Fallback if no specific rows
    if (empty($workspaces)) {
        $workspaces = $pdo->query("SELECT id, key, title, description, category FROM roles WHERE key IN ('principal', 'admin', 'teacher', 'student', 'parent')")->fetchAll();
    }

    jsonResponse(['success' => true, 'workspaces' => $workspaces]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'select') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $roleKey = trim($input['role_key'] ?? 'principal');

    $stmt = $pdo->prepare("SELECT * FROM roles WHERE key = ?");
    $stmt->execute([$roleKey]);
    $role = $stmt->fetch();

    if (!$role) {
        jsonResponse(['error' => 'Invalid role specified.'], 404);
    }

    $_SESSION['active_role_key'] = $roleKey;

    jsonResponse([
        'success' => true,
        'role' => $role,
        'redirect' => 'index.php?page=dashboard'
    ]);
}

jsonResponse(['error' => 'Invalid action.'], 400);
