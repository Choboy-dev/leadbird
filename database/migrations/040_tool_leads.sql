-- Email and WhatsApp number left by visitors who use the free public tools
-- (chat link generator, QR code generator and similar). The welcome message is never stored.

CREATE TABLE IF NOT EXISTS tool_leads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tool VARCHAR(50) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tool_leads_email (email),
    KEY idx_tool_leads_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
