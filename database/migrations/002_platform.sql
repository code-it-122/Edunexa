-- 002_platform.sql – Phase 2 platform tables and alterations
-- Run once: mysql -u root edunexa < database/migrations/002_platform.sql
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------
-- 1. Notifications
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    category    ENUM('Academic','Finance','Attendance','Transport','Hostel','Safety','Events','Approval','System') NOT NULL,
    title       VARCHAR(150) NOT NULL,
    message     TEXT NOT NULL,
    link        VARCHAR(250) NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user (user_id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- 2. Password reset tokens (forgot‑password flow)
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    token       CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used        TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- 3. Campuses (multi‑school / multi‑campus support)
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS campuses (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    address     VARCHAR(200) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add campus_id to users (nullable – not all users are campus‑specific)
ALTER TABLE users ADD COLUMN campus_id INT UNSIGNED NULL AFTER role;
ALTER TABLE users ADD CONSTRAINT fk_user_campus FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE SET NULL;

-- Add campus_id to classes (each class belongs to a campus)
ALTER TABLE classes ADD COLUMN campus_id INT UNSIGNED NULL AFTER academic_year_id;
ALTER TABLE classes ADD CONSTRAINT fk_class_campus FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE SET NULL;

-- -------------------------------------------------------------------
-- 4. Login attempts (IP throttling)
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED NULL,
    ip_address   VARCHAR(45) NOT NULL,
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    last_attempt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ip (ip_address),
    CONSTRAINT fk_login_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- 5. Audit logs
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    action      VARCHAR(50) NOT NULL,
    entity      VARCHAR(50) NOT NULL,
    entity_id   INT UNSIGNED NULL,
    meta        JSON NULL,
    ip_address  VARCHAR(45) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user (user_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
