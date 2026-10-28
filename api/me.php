<?php
/**
 * CASPA School OS – api/me.php
 *
 * Returns the currently authenticated user's id, name, and role.
 *
 * Method:  GET (also accepts POST)
 * Auth:    required (returns 401 JSON if not logged in)
 *
 * Success response:
 *   {"ok":true,"data":{"id":1,"name":"Dr. Priya Nair","role":"principal"}}
 *
 * 401 response (not logged in):
 *   {"ok":false,"error":"Not authenticated."}
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/response.php';
require_once ROOT . '/includes/csrf_guard.php';

// Enforce login – sends 401 JSON and exits if not authenticated
require_login();

$user = current_user();   // guaranteed non-null after require_login()

json_ok([
    'id'   => $user['id'],
    'name' => $user['name'],
    'role' => $user['role'],
]);
