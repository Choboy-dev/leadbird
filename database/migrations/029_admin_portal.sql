-- Admin portal: roles, audit log, feature flags, impersonation sessions

ALTER TABLE users
    ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'user' AFTER email,
    ADD COLUMN is_suspended TINYINT(1) NOT NULL DEFAULT 0 AFTER role,
    ADD COLUMN suspended_at TIMESTAMP NULL AFTER is_suspended,
    ADD INDEX idx_users_role (role),
    ADD INDEX idx_users_suspended (is_suspended);

CREATE TABLE IF NOT EXISTS admin_audit_log (
    id CHAR(36) NOT NULL PRIMARY KEY,
    admin_user_id CHAR(36) NOT NULL,
    action VARCHAR(64) NOT NULL,
    target_type VARCHAR(32) NULL,
    target_id VARCHAR(64) NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_audit_created (created_at DESC),
    INDEX idx_admin_audit_admin (admin_user_id, created_at DESC),
    INDEX idx_admin_audit_action (action, created_at DESC),
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_feature_flags (
    flag_key VARCHAR(64) NOT NULL PRIMARY KEY,
    flag_value JSON NOT NULL,
    description VARCHAR(255) NULL,
    updated_by CHAR(36) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_impersonation_sessions (
    id CHAR(36) NOT NULL PRIMARY KEY,
    admin_user_id CHAR(36) NOT NULL,
    target_user_id CHAR(36) NOT NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_impersonation_active (is_active, started_at DESC),
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_feature_flags (flag_key, flag_value, description) VALUES
    ('maintenance_mode', 'false', 'When true, non-admin users see a maintenance notice'),
    ('signup_enabled', 'true', 'Allow new user signups'),
    ('ai_replies_enabled', 'true', 'Global kill switch for AI automation replies')
ON DUPLICATE KEY UPDATE flag_key = flag_key;
