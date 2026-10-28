<?php
/**
 * CSRF protection helper functions.
 *
 * Generates a token per user session and verifies it on state‑changing requests.
 */
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Generate (or retrieve) a CSRF token stored in the session.
 *
 * @return string The CSRF token.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // 32‑byte random token, hex‑encoded (64 chars).
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a token supplied by the client.
 *
 * @param string|null $token Token from request (POST/GET/HEADER).
 * @return bool True if token is valid for the current session.
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    // Use hash_equals to mitigate timing attacks.
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>