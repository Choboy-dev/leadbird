-- Meta data deletion callback requests (Facebook App Review requirement).
-- Meta sends a signed_request, we purge Meta-sourced data, then expose a
-- public status page keyed by confirmation_code.

CREATE TABLE meta_deletion_requests (
    id CHAR(36) PRIMARY KEY,
    confirmation_code VARCHAR(40) NOT NULL,
    meta_user_id VARCHAR(64) NOT NULL,
    source VARCHAR(32) NOT NULL DEFAULT 'callback',
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    businesses_affected INT NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    UNIQUE KEY uk_meta_deletion_code (confirmation_code),
    INDEX idx_meta_deletion_user (meta_user_id)
);
