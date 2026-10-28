<?php
/**
 * CASPA School OS – api/announcements.php
 *
 * GET  ?role=  Returns published announcements visible to the caller's role.
 *              principal/admin see all; others see only announcements whose
 *              audience SET includes their role.
 *
 * POST (principal | admin only)
 *   title     required, max 200
 *   body      required
 *   audience  required, comma-separated subset of principal,admin,teacher,student,parent
 *   expires_at  optional YYYY-MM-DD
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

// ── GET ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $role   = $user['role'];
    $limit  = min((int)($_GET['limit'] ?? 20), 100);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    if (in_array($role, ['principal', 'admin'], true)) {
        // See all published announcements
        $sql = "
            SELECT a.id, a.title, a.body, a.audience, a.published_at, a.expires_at,
                   a.created_at, u.name AS author_name
            FROM   announcements a
            JOIN   users u ON u.id = a.created_by
            WHERE  a.published_at IS NOT NULL
            ORDER  BY a.published_at DESC, a.id DESC
            LIMIT  :lim OFFSET :off
        ";
        $st = $pdo->prepare($sql);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
    } else {
        // Scope by audience SET containing the user's role
        $sql = "
            SELECT a.id, a.title, a.body, a.audience, a.published_at, a.expires_at,
                   a.created_at, u.name AS author_name
            FROM   announcements a
            JOIN   users u ON u.id = a.created_by
            WHERE  FIND_IN_SET(:role, a.audience) > 0
              AND  a.published_at IS NOT NULL
              AND  a.published_at <= NOW()
              AND  (a.expires_at IS NULL OR a.expires_at >= NOW())
            ORDER  BY a.published_at DESC, a.id DESC
            LIMIT  :lim OFFSET :off
        ";
        $st = $pdo->prepare($sql);
        $st->bindValue(':role', $role, PDO::PARAM_STR);
        $st->bindValue(':lim',  $limit,  PDO::PARAM_INT);
        $st->bindValue(':off',  $offset, PDO::PARAM_INT);
        $st->execute();
    }

    json_ok(['announcements' => $st->fetchAll()]);
}

// ── POST ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['principal', 'admin']);

    // Accept JSON body or form-encoded
    $input = $_POST;
    $raw   = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $input = array_merge($input, $decoded);
        }
    }

    $title     = trim((string)($input['title'] ?? ''));
    $body      = trim((string)($input['body']  ?? ''));
    $audienceRaw = trim((string)($input['audience'] ?? ''));
    $expiresAt = trim((string)($input['expires_at'] ?? ''));

    // Validate title
    if ($title === '') {
        json_fail('Title is required.', 422);
    }
    if (mb_strlen($title) > 200) {
        json_fail('Title must be at most 200 characters.', 422);
    }

    // Validate body
    if ($body === '') {
        json_fail('Message body is required.', 422);
    }

    // Validate audience
    $allowedRoles = ['principal', 'admin', 'teacher', 'student', 'parent'];
    if ($audienceRaw === '') {
        json_fail('Audience is required.', 422);
    }
    $audienceParts = array_filter(array_map('trim', explode(',', $audienceRaw)));
    foreach ($audienceParts as $ap) {
        if (!in_array($ap, $allowedRoles, true)) {
            json_fail("Invalid audience value: '{$ap}'.", 422);
        }
    }
    $audienceValue = implode(',', $audienceParts);

    // Validate expires_at (optional)
    $expiresAtValue = null;
    if ($expiresAt !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiresAt)) {
            json_fail('Expiry date must be in YYYY-MM-DD format.', 422);
        }
        if ($expiresAt < date('Y-m-d')) {
            json_fail('Expiry date cannot be in the past.', 422);
        }
        $expiresAtValue = $expiresAt . ' 23:59:59';
    }

    $st = $pdo->prepare("
        INSERT INTO announcements (title, body, audience, created_by, published_at, expires_at)
        VALUES (:title, :body, :audience, :created_by, NOW(), :expires_at)
    ");
    $st->execute([
        ':title'      => $title,
        ':body'       => $body,
        ':audience'   => $audienceValue,
        ':created_by' => (int)$user['id'],
        ':expires_at' => $expiresAtValue,
    ]);

    $newId = (int)$pdo->lastInsertId();

    json_ok([
        'id'      => $newId,
        'message' => "Announcement '{$title}' published successfully.",
    ], 201);
}

json_fail('Method not allowed.', 405);
