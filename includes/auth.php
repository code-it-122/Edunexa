<?php
/**
 * CASPA School OS – Authentication helpers
 *
 * Functions
 *   session_boot()           – start session with secure settings
 *   current_user()           – return session user array or null
 *   require_login()          – redirect/abort if not logged in
 *   require_role($roles)     – redirect/abort if role not allowed
 *   login_user($user)        – write session, regenerate ID
 *   logout_user()            – destroy session
 *   allowed_class_ids($user) – return int[] of class IDs the user may access
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// ---------------------------------------------------------------------------
// Session bootstrap
// ---------------------------------------------------------------------------

function session_boot(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // set true when HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ---------------------------------------------------------------------------
// Current user
// ---------------------------------------------------------------------------

/**
 * Returns the authenticated user array from session, or null.
 *
 * @return array{id:int,name:string,email:string,role:string}|null
 */
function current_user(): ?array
{
    session_boot();
    return $_SESSION['user'] ?? null;
}

// ---------------------------------------------------------------------------
// Guards
// ---------------------------------------------------------------------------

/**
 * Abort with 401 JSON if the request comes from an API endpoint,
 * otherwise redirect to login page.
 */
function require_login(): void
{
    if (current_user() === null) {
        _auth_fail(401, 'Not authenticated.');
    }
}

/**
 * @param string|string[] $roles  Allowed role(s).
 */
function require_role(string|array $roles): void
{
    require_login();

    $roles = (array) $roles;
    $user  = current_user();

    if (!in_array($user['role'], $roles, true)) {
        _auth_fail(403, 'Forbidden.');
    }
}

/** Internal – respond appropriately based on whether it's an API call. */
function _auth_fail(int $status, string $message): never
{
    http_response_code($status);

    $is_api = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');

    if ($is_api) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $message]);
    } else {
        header('Location: ' . APP_URL . '/index.php?error=' . urlencode($message));
    }

    exit;
}

// ---------------------------------------------------------------------------
// Login / logout
// ---------------------------------------------------------------------------

/**
 * Write user data into session and regenerate the session ID.
 *
 * @param array{id:int,name:string,email:string,role:string} $user
 */
function login_user(array $user): void
{
    session_boot();
    session_regenerate_id(true);   // prevent session fixation
    $_SESSION['user'] = [
        'id'    => (int)    $user['id'],
        'name'  => (string) $user['name'],
        'email' => (string) $user['email'],
        'role'  => (string) $user['role'],
    ];
}

/** Destroy the current session completely. */
function logout_user(): void
{
    session_boot();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------------------------------------------------------------------------
// Data-scope helper
// ---------------------------------------------------------------------------

/**
 * Return the class IDs that the given user is permitted to access.
 *
 * Rules (server-side; hiding menus is NOT security):
 *   principal / admin → every class in the system
 *   teacher           → classes where they appear in class_subjects.teacher_id
 *   student           → the single class from their students row
 *   parent            → classes of all students linked via parent_student
 *
 * @param array{id:int,role:string,...} $user  Session user array from current_user()
 * @return int[]  Sorted, deduplicated list of class IDs (may be empty)
 */
function allowed_class_ids(array $user): array
{
    require_once __DIR__ . '/db.php';

    $pdo  = db();
    $role = $user['role'];
    $uid  = (int) $user['id'];
    $ids  = [];

    switch ($role) {
        case 'principal':
        case 'admin':
            $rows = $pdo->query('SELECT id FROM classes')->fetchAll(PDO::FETCH_COLUMN);
            $ids  = array_map('intval', $rows);
            break;

        case 'teacher':
            $st = $pdo->prepare('
                SELECT DISTINCT class_id
                FROM   class_subjects
                WHERE  teacher_id = :uid
            ');
            $st->execute([':uid' => $uid]);
            $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            break;

        case 'student':
            $st = $pdo->prepare('
                SELECT class_id
                FROM   students
                WHERE  user_id = :uid
                LIMIT  1
            ');
            $st->execute([':uid' => $uid]);
            $classId = $st->fetchColumn();
            $ids = $classId !== false ? [(int) $classId] : [];
            break;

        case 'parent':
            $st = $pdo->prepare('
                SELECT DISTINCT s.class_id
                FROM   parent_student ps
                JOIN   parents        p  ON p.id      = ps.parent_id
                JOIN   students       s  ON s.id      = ps.student_id
                WHERE  p.user_id = :uid
                  AND  s.class_id IS NOT NULL
            ');
            $st->execute([':uid' => $uid]);
            $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            break;
    }

    sort($ids);
    return array_values(array_unique($ids));
}
