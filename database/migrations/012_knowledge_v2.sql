ALTER TABLE business_knowledge_chunks
    ADD COLUMN embedding_json MEDIUMTEXT NULL AFTER metadata;

ALTER TABLE business_knowledge_sync
    ADD COLUMN last_embedding_sync_at TIMESTAMP NULL AFTER last_social_sync_at,
    ADD COLUMN embedded_chunk_count INT NOT NULL DEFAULT 0 AFTER social_post_count;

ALTER TABLE messages
    ADD COLUMN message_media_url VARCHAR(512) NULL AFTER message_text,
    ADD COLUMN message_media_type VARCHAR(32) NULL AFTER message_media_url,
    ADD COLUMN referral_context TEXT NULL AFTER message_media_type;
