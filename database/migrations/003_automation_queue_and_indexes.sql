-- OAuth state payload for Meta channel connect (optional JSON context).
ALTER TABLE oauth_states
    ADD COLUMN context TEXT NULL AFTER code_verifier;

-- Faster tenant lookup from webhook platform_account_id.
CREATE INDEX idx_connected_accounts_platform_account
    ON connected_accounts (platform, platform_account_id);

-- Async message processing queue (cron: scripts/process-automation-queue.php).
CREATE TABLE automation_queue (
    id CHAR(36) PRIMARY KEY,
    job_type VARCHAR(50) NOT NULL DEFAULT 'process_message',
    payload TEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    last_error TEXT NULL,
    INDEX idx_automation_queue_pending (status, available_at)
);
