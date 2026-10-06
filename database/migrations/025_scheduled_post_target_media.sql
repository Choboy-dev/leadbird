-- Per-target media so Facebook and Instagram can publish different attachments.
CREATE TABLE IF NOT EXISTS scheduled_post_target_media (
    id CHAR(36) NOT NULL PRIMARY KEY,
    scheduled_post_target_id CHAR(36) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    media_type VARCHAR(32) NOT NULL DEFAULT 'image',
    media_url VARCHAR(512) NOT NULL,
    media_storage_path VARCHAR(512) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scheduled_post_target_media_target (scheduled_post_target_id, sort_order),
    CONSTRAINT fk_scheduled_post_target_media_target FOREIGN KEY (scheduled_post_target_id) REFERENCES scheduled_post_targets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
