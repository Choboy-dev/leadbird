-- Negotiation (Vendor+), proactive follow-ups + receipts (Shop+)

ALTER TABLE business_settings
    ADD COLUMN negotiation_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_owner_on_complaint,
    ADD COLUMN max_discount_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00 AFTER negotiation_enabled,
    ADD COLUMN negotiation_rules TEXT NULL AFTER max_discount_percent,
    ADD COLUMN upsell_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER negotiation_rules,
    ADD COLUMN upsell_notes TEXT NULL AFTER upsell_enabled,
    ADD COLUMN followup_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER upsell_notes,
    ADD COLUMN followup_delay_hours INT NOT NULL DEFAULT 6 AFTER followup_enabled,
    ADD COLUMN followup_max_attempts INT NOT NULL DEFAULT 2 AFTER followup_delay_hours,
    ADD COLUMN followup_interval_hours INT NOT NULL DEFAULT 12 AFTER followup_max_attempts,
    ADD COLUMN followup_notes TEXT NULL AFTER followup_interval_hours,
    ADD COLUMN receipt_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER followup_notes,
    ADD COLUMN receipt_message TEXT NULL AFTER receipt_enabled;

CREATE TABLE IF NOT EXISTS conversation_followups (
    id CHAR(36) NOT NULL,
    business_id CHAR(36) NOT NULL,
    conversation_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NULL,
    platform VARCHAR(32) NULL,
    attempt_count INT NOT NULL DEFAULT 0,
    last_followup_at TIMESTAMP NULL DEFAULT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conversation_followups_conversation (conversation_id),
    KEY idx_conversation_followups_business (business_id),
    KEY idx_conversation_followups_status (status),
    CONSTRAINT fk_conversation_followups_business FOREIGN KEY (business_id) REFERENCES businesses (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_followups_conversation FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
