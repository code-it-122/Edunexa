<?php
// api/campus.php - Campus Selection API
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required.'], 401);
}

$pdo = getDbConnection();
$action = $_REQUEST['action'] ?? '';

if ($action === 'list') {
    $campuses = $pdo->query("SELECT * FROM campuses WHERE status = 'active'")->fetchAll();
    jsonResponse(['success' => true, 'campuses' => $campuses]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'select') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $campusId = (int)($input['campus_id'] ?? 1);

    $campus = $pdo->prepare("SELECT * FROM campuses WHERE id = ?");
    $campus->execute([$campusId]);
    $found = $campus->fetch();

    if (!$found) {
        jsonResponse(['error' => 'Campus not found.'], 404);
    }

    $_SESSION['active_campus_id'] = $campusId;
    $pdo->prepare("UPDATE users SET default_campus_id = ? WHERE id = ?")->execute([$campusId, $_SESSION['user_id']]);

    jsonResponse([
        'success' => true,
        'campus' => $found,
        'redirect' => 'index.php?page=choose-workspace'
    ]);
}

jsonResponse(['error' => 'Invalid action.'], 400);
