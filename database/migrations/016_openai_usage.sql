CREATE TABLE IF NOT EXISTS openai_usage_events (
    id CHAR(36) NOT NULL PRIMARY KEY,
    business_id VARCHAR(64) NOT NULL,
    feature VARCHAR(64) NOT NULL,
    model VARCHAR(64) NOT NULL,
    prompt_tokens INT UNSIGNED NOT NULL DEFAULT 0,
    completion_tokens INT UNSIGNED NOT NULL DEFAULT 0,
    total_tokens INT UNSIGNED NOT NULL DEFAULT 0,
    cost_usd DECIMAL(12, 6) NOT NULL DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_openai_usage_business_month (business_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
