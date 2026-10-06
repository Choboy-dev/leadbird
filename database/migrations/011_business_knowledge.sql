CREATE TABLE IF NOT EXISTS business_knowledge_chunks (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    source_type VARCHAR(50) NOT NULL,
    source_id VARCHAR(255) NULL,
    title VARCHAR(255) NULL,
    content TEXT NOT NULL,
    keywords TEXT NULL,
    image_url VARCHAR(512) NULL,
    metadata TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bkc_business (business_id),
    INDEX idx_bkc_business_source (business_id, source_type),
    INDEX idx_bkc_business_source_id (business_id, source_type, source_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS business_knowledge_sync (
    business_id CHAR(36) PRIMARY KEY,
    last_manual_sync_at TIMESTAMP NULL,
    last_social_sync_at TIMESTAMP NULL,
    social_sync_status VARCHAR(32) NOT NULL DEFAULT 'idle',
    social_post_count INT NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);
