-- Migration: Approvals and Role Builder
-- Adds tables for generic approval workflows and a flexible role/permission system.
-- Run after existing migrations.

-- Approvals table stores generic approval requests.
CREATE TABLE IF NOT EXISTS approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id BIGINT UNSIGNED NOT NULL,
    approver_role VARCHAR(32) NOT NULL, -- role expected to act on this request
    module VARCHAR(64) NOT NULL,        -- e.g., 'leave', 'lesson_plan', 'certificate', 'fee_concession'
    record_id BIGINT UNSIGNED NOT NULL, -- id of the underlying record
    current_stage VARCHAR(64) NOT NULL,
    required_action VARCHAR(64) NOT NULL,
    sla_days INT UNSIGNED NOT NULL DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('pending','approved','rejected','changes_requested') NOT NULL DEFAULT 'pending',
    history JSON NULL -- optional JSON array of actions
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role definition table (built‑in roles already exist in users.role column).
CREATE TABLE IF NOT EXISTS roles (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL UNIQUE,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permission definitions for modules and actions.
CREATE TABLE IF NOT EXISTS permissions (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(64) NOT NULL,               -- e.g., 'students', 'classes', 'lesson_plans'
    permission_type ENUM('view','create','edit','delete','approve','publish','export','print') NOT NULL,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role → Permission mapping with optional scope and expiry.
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id SMALLINT UNSIGNED NOT NULL,
    permission_id SMALLINT UNSIGNED NOT NULL,
    scope ENUM('own','assigned','class','campus','school') NOT NULL DEFAULT 'own',
    fields JSON NULL,                  -- list of field names allowed/blocked
    expires_at DATETIME NULL,          -- NULL = never expires
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User ↔ Role assignments (many‑to‑many to support multiple roles per user).
CREATE TABLE IF NOT EXISTS user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id SMALLINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for quick look‑ups.
CREATE INDEX idx_approvals_requester ON approvals(requester_id);
CREATE INDEX idx_approvals_approver_role ON approvals(approver_role);
CREATE INDEX idx_role_permissions_role ON role_permissions(role_id);
