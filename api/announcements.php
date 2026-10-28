<?php
/**
 * CASPA School OS – api/announcements.php
 *
 * GET   Role-scoped list of published, non-expired announcements.
 *         principal / admin : every announcement (they manage them)
 *         teacher / student / parent : only those whose audience includes their role
 *       Optional ?limit=N (1-50, default 20)
 *
 * POST  principal / admin only. JSON body:
 *         { title, body, audience: ["teacher","student",...] }
 *       Published immediately. Returns the created announcement.
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

const ANNOUNCEMENT_ROLES = ['principal', 'admin', 'teacher', 'student', 'parent'];

// ── GET ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    $limit = max(1, min(50, $limit));

    $isManager = in_array($user['role'], ['principal', 'admin'], true);

    $sql = "
        SELECT a.id, a.title, a.body, a.audience, a.published_at, a.expires_at,
               u.name AS author_name
        FROM announcements a
        JOIN users u ON u.id = a.created_by
        WHERE a.published_at IS NOT NULL
          AND a.published_at <= NOW()
          AND (a.expires_at IS NULL OR a.expires_at >= NOW())
    ";
    $params = [];

    if (!$isManager) {
        $sql .= " AND FIND_IN_SET(:role, a.audience) > 0";
        $params[':role'] = $user['role'];
    }

    // $limit is an int clamped above, safe to inline (LIMIT cannot be bound with emulation off)
    $sql .= " ORDER BY a.published_at DESC, a.id DESC LIMIT " . $limit;

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    foreach ($rows as &$r) {
        $r['audience'] = $r['audience'] === '' ? [] : explode(',', (string) $r['audience']);
    }
    unset($r);

    json_ok([
        'announcements' => $rows,
        'can_create'    => $isManager,
    ]);
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['principal', 'admin']);

    $input   = $_POST;
    $rawBody = file_get_contents('php://input');
    if ($rawBody) {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $input = array_merge($input, $decoded);
        }
    }

    $title    = trim((string) ($input['title'] ?? ''));
    $body     = trim((string) ($input['body'] ?? ''));
    $audience = $input['audience'] ?? [];

    if ($title === '') {
        json_fail('Title is required.', 422);
    }
    if (mb_strlen($title) > 200) {
        json_fail('Title must be at most 200 characters.', 422);
    }
    if ($body === '') {
        json_fail('Message body is required.', 422);
    }
    if (mb_strlen($body) > 5000) {
        json_fail('Message body must be at most 5000 characters.', 422);
    }
    if (!is_array($audience) || count($audience) === 0) {
        json_fail('Choose at least one audience.', 422);
    }

    $clean = [];
    foreach ($audience as $a) {
        $a = strtolower(trim((string) $a));
        if (!in_array($a, ANNOUNCEMENT_ROLES, true)) {
            json_fail('Invalid audience value.', 422);
        }
        $clean[$a] = true;
    }
    $audienceStr = implode(',', array_keys($clean));

    $st = $pdo->prepare("
        INSERT INTO announcements (title, body, audience, created_by, published_at)
        VALUES (:title, :body, :audience, :uid, NOW())
    ");
    $st->execute([
        ':title'    => $title,
        ':body'     => $body,
        ':audience' => $audienceStr,
        ':uid'      => (int) $user['id'],
    ]);

    json_ok([
        'id'       => (int) $pdo->lastInsertId(),
        'title'    => $title,
        'audience' => array_keys($clean),
        'message'  => 'Announcement published.',
    ], 201);
}

json_fail('Method not allowed.', 405);
