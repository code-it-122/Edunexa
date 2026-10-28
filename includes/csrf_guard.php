<?php
/**
 * CSRF protection guard for state‑changing API endpoints.
 *
 * Include this file in any API script that handles POST, PUT, DELETE
 * (or any request that mutates server state). It verifies the token sent
 * via the `X‑CSRF‑Token` header (or fallback `csrf_token` form field).
 * On failure it returns a JSON error with HTTP 403 status.
 * GET requests are ignored.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

require_once ROOT . '/includes/csrf.php';
require_once ROOT . '/includes/response.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    // Prefer header, then POST field (for form submissions).
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
    if (!verify_csrf_token($token)) {
        json_fail('Invalid CSRF token.', 403);
    }
    // Token is valid – continue execution of the endpoint.
}
?>