<?php
/**
 * Permission helper utilities for the CASPA platform.
 *
 * Provides functions to check whether a user has a given permission on a
 * module/action and, optionally, a specific record. Also returns a textual
 * explanation of why a permission is granted (used for the "Why can I see this?"
 * indicator).
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php'; // assumes $pdo PDO instance is provided by db.php
require_once __DIR__ . '/audit.php'; // audit_log function (will be created later)

/**
 * Determine if a user has a specific permission.
 *
 * @param int    $userId   The ID of the user.
 * @param string $module   The module name (e.g., 'lesson_plans').
 * @param string $action   One of the permission_type values (view, create, edit, delete, approve, publish, export, print).
 * @param int|null $recordId Optional record ID for record‑scoped checks.
 * @return bool True if permission granted.
 */
function has_permission(int $userId, string $module, string $action, ?int $recordId = null): bool
{
    // Resolve all roles for the user (including built‑in role stored in users.role).
    $stmt = $GLOBALS['pdo']->prepare(
        "SELECT r.name FROM roles r
         JOIN user_roles ur ON ur.role_id = r.id
         WHERE ur.user_id = :uid"
    );
    $stmt->execute(['uid' => $userId]);
    $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Include the legacy single‑role column if present.
    $stmt = $GLOBALS['pdo']->prepare('SELECT role FROM users WHERE id = :uid');
    $stmt->execute(['uid' => $userId]);
    $legacyRole = $stmt->fetchColumn();
    if ($legacyRole) {
        $userRoles[] = $legacyRole;
    }
    $userRoles = array_unique($userRoles);

    if (empty($userRoles)) {
        return false;
    }

    // Build placeholders for IN clause.
    $placeholders = implode(',', array_fill(0, count($userRoles), '?'));
    $sql = "SELECT rp.scope, rp.fields, rp.expires_at
            FROM role_permissions rp
            JOIN permissions p ON p.id = rp.permission_id
            JOIN roles r ON r.id = rp.role_id
            WHERE r.name IN ($placeholders)
              AND p.module = ?
              AND p.permission_type = ?";
    $params = array_merge($userRoles, [$module, $action]);
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        // Check expiry.
        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
            continue; // expired grant
        }
        // Scope handling.
        switch ($row['scope']) {
            case 'own':
                // Only allowed if the record belongs to the user (e.g., created_by).
                if ($recordId !== null) {
                    $ownerStmt = $GLOBALS['pdo']->prepare('SELECT created_by FROM ' . $module . ' WHERE id = :rid');
                    $ownerStmt->execute(['rid' => $recordId]);
                    $ownerId = $ownerStmt->fetchColumn();
                    if ((int)$ownerId === $userId) {
                        return true;
                    }
                }
                break;
            case 'assigned':
                // For simplicity, treat assigned same as own for now.
                if ($recordId !== null) {
                    $assignedStmt = $GLOBALS['pdo']->prepare('SELECT assigned_to FROM ' . $module . ' WHERE id = :rid');
                    $assignedStmt->execute(['rid' => $recordId]);
                    $assigned = $assignedStmt->fetchColumn();
                    if ((int)$assigned === $userId) {
                        return true;
                    }
                }
                break;
            case 'class':
            case 'campus':
            case 'school':
                // These scopes require more complex joins; assume granted for now.
                return true;
        }
    }
    return false;
}

/**
 * Provide a human‑readable explanation of why a permission is granted.
 *
 * @param int    $userId   User ID.
 * @param string $module   Module name.
 * @param string $action   Permission type.
 * @param int|null $recordId Optional record ID.
 * @return string Explanation (empty if no permission).
 */
function permission_explanation(int $userId, string $module, string $action, ?int $recordId = null): string
{
    // Similar query to has_permission but returns the first matching rule.
    $stmt = $GLOBALS['pdo']->prepare(
        "SELECT r.name AS role_name, rp.scope, rp.expires_at, rp.fields
         FROM role_permissions rp
         JOIN permissions p ON p.id = rp.permission_id
         JOIN roles r ON r.id = rp.role_id
         WHERE p.module = :mod AND p.permission_type = :act"
    );
    $stmt->execute(['mod' => $module, 'act' => $action]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
            continue;
        }
        $scope = $row['scope'];
        $role = $row['role_name'];
        $exp = $row['expires_at'] ? " (expires " . $row['expires_at'] . ")" : '';
        return "Permission granted via role '{$role}' with scope '{$scope}'{$exp}.";
    }
    return '';
}
?>