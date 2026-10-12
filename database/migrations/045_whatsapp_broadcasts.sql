-- WhatsApp broadcasts: Meta-approved templates, opted-in contacts, and batched sends.

ALTER TABLE connected_accounts
    ADD COLUMN waba_id VARCHAR(64) NULL AFTER linked_page_id;

CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    connected_account_id CHAR(36) NOT NULL,
    waba_id VARCHAR(64) NOT NULL,
    meta_template_id VARCHAR(64) NULL,
    name VARCHAR(512) NOT NULL,
    title VARCHAR(120) NOT NULL,
    language VARCHAR(16) NOT NULL DEFAULT 'en',
    category VARCHAR(32) NOT NULL,
    body TEXT NOT NULL,
    footer VARCHAR(60) NULL,
    uses_name TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'PENDING',
    rejected_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_wa_templates_business (business_id, created_at DESC),
    UNIQUE KEY uq_wa_templates_waba_name (waba_id, name, language)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS broadcast_contacts (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    name VARCHAR(120) NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'manual',
    opted_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY uq_broadcast_contacts_phone (business_id, phone),
    INDEX idx_broadcast_contacts_active (business_id, unsubscribed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS broadcasts (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    connected_account_id CHAR(36) NOT NULL,
    template_id CHAR(36) NOT NULL,
    name VARCHAR(120) NOT NULL,
    name_fallback VARCHAR(60) NOT NULL DEFAULT 'there',
    status VARCHAR(20) NOT NULL DEFAULT 'sending',
    pause_reason VARCHAR(255) NULL,
    total_recipients INT NOT NULL DEFAULT 0,
    tokens_charged INT NOT NULL DEFAULT 0,
    created_by CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_broadcasts_business (business_id, created_at DESC),
    INDEX idx_broadcasts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS broadcast_recipients (
    id CHAR(36) PRIMARY KEY,
    broadcast_id CHAR(36) NOT NULL,
    contact_id CHAR(36) NULL,
    phone VARCHAR(20) NOT NULL,
    name VARCHAR(120) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'queued',
    wa_message_id VARCHAR(128) NULL,
    error_code VARCHAR(16) NULL,
    error_message VARCHAR(255) NULL,
    sent_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (broadcast_id) REFERENCES broadcasts(id) ON DELETE CASCADE,
    INDEX idx_broadcast_recipients_queue (broadcast_id, status),
    INDEX idx_broadcast_recipients_message (wa_message_id),
    INDEX idx_broadcast_recipients_sent (phone, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
