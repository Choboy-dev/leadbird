CREATE TABLE IF NOT EXISTS business_knowledge_links (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    social_chunk_id CHAR(36) NOT NULL,
    offering_id CHAR(36) NOT NULL,
    match_score DECIMAL(5, 4) NULL,
    link_source VARCHAR(20) NOT NULL DEFAULT 'auto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_bkl_social_offering (social_chunk_id, offering_id),
    INDEX idx_bkl_business (business_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (social_chunk_id) REFERENCES business_knowledge_chunks(id) ON DELETE CASCADE,
    FOREIGN KEY (offering_id) REFERENCES offerings(id) ON DELETE CASCADE
);

ALTER TABLE business_knowledge_sync
    ADD COLUMN last_learning_sync_at TIMESTAMP NULL AFTER last_embedding_sync_at;

ALTER TABLE business_knowledge_sync
    ADD COLUMN learning_memory_count INT NOT NULL DEFAULT 0 AFTER embedded_chunk_count;

ALTER TABLE business_knowledge_sync
    ADD COLUMN linked_post_count INT NOT NULL DEFAULT 0 AFTER learning_memory_count;
