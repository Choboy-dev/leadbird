-- Social media management: scheduled posts and per-channel publish targets

CREATE TABLE IF NOT EXISTS scheduled_posts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    created_by_user_id CHAR(36) NULL,
    caption TEXT NOT NULL,
    hashtags TEXT NULL,
    media_type VARCHAR(32) NOT NULL DEFAULT 'image',
    media_url VARCHAR(512) NULL,
    media_storage_path VARCHAR(512) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    ai_assisted TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_scheduled_posts_business (business_id, created_at DESC),
    INDEX idx_scheduled_posts_due (status, scheduled_at),
    CONSTRAINT fk_scheduled_posts_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scheduled_post_targets (
    id CHAR(36) NOT NULL PRIMARY KEY,
    scheduled_post_id CHAR(36) NOT NULL,
    connected_account_id CHAR(36) NOT NULL,
    platform VARCHAR(32) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    platform_post_id VARCHAR(255) NULL,
    platform_permalink VARCHAR(512) NULL,
    error_message TEXT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_scheduled_post_targets_post (scheduled_post_id),
    INDEX idx_scheduled_post_targets_status (status),
    CONSTRAINT fk_scheduled_post_targets_post FOREIGN KEY (scheduled_post_id) REFERENCES scheduled_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_scheduled_post_targets_account FOREIGN KEY (connected_account_id) REFERENCES connected_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
