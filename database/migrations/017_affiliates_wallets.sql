-- Lead Bird affiliates, wallets, merchant pay, disputes

ALTER TABLE users
    ADD COLUMN referred_by_affiliate_id CHAR(36) NULL AFTER google_id,
    ADD INDEX idx_users_referred_by (referred_by_affiliate_id);

CREATE TABLE IF NOT EXISTS affiliates (
    id CHAR(36) NOT NULL PRIMARY KEY,
    user_id CHAR(36) NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(32) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    kyc_status VARCHAR(32) NOT NULL DEFAULT 'none',
    flw_psa_id VARCHAR(64) NULL,
    flw_barter_id VARCHAR(64) NULL,
    flw_account_reference VARCHAR(64) NULL,
    bank_code VARCHAR(16) NULL,
    bank_name VARCHAR(120) NULL,
    account_number VARCHAR(32) NULL,
    account_name VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_affiliates_code (code),
    UNIQUE KEY uk_affiliates_email (email),
    UNIQUE KEY uk_affiliates_user (user_id),
    INDEX idx_affiliates_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_attributions (
    id CHAR(36) NOT NULL PRIMARY KEY,
    affiliate_id CHAR(36) NOT NULL,
    visitor_key VARCHAR(64) NOT NULL,
    user_id CHAR(36) NULL,
    landing_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    converted_at TIMESTAMP NULL,
    INDEX idx_attr_visitor (visitor_key, created_at DESC),
    INDEX idx_attr_affiliate (affiliate_id, created_at DESC),
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_commissions (
    id CHAR(36) NOT NULL PRIMARY KEY,
    affiliate_id CHAR(36) NOT NULL,
    referred_user_id CHAR(36) NOT NULL,
    subscription_id CHAR(36) NOT NULL,
    tx_ref VARCHAR(255) NOT NULL,
    plan_slug VARCHAR(32) NOT NULL,
    gross_amount DECIMAL(12, 2) NOT NULL,
    commission_amount DECIMAL(12, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    hold_until TIMESTAMP NULL,
    available_at TIMESTAMP NULL,
    funded_at TIMESTAMP NULL,
    clawed_back_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_commission_subscription (subscription_id),
    UNIQUE KEY uk_commission_tx_ref (tx_ref),
    INDEX idx_commission_affiliate_status (affiliate_id, status),
    INDEX idx_commission_hold (status, hold_until),
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_payouts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    affiliate_id CHAR(36) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    bank_code VARCHAR(16) NOT NULL,
    bank_name VARCHAR(120) NULL,
    account_number VARCHAR(32) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    flw_transfer_id VARCHAR(64) NULL,
    flw_reference VARCHAR(128) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    failure_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payouts_affiliate (affiliate_id, created_at DESC),
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_wallets (
    id CHAR(36) NOT NULL PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    flw_psa_id VARCHAR(64) NULL,
    flw_barter_id VARCHAR(64) NULL,
    flw_account_reference VARCHAR(64) NULL,
    virtual_account_number VARCHAR(32) NULL,
    virtual_bank_name VARCHAR(120) NULL,
    bank_code VARCHAR(16) NULL,
    bank_name VARCHAR(120) NULL,
    account_number VARCHAR(32) NULL,
    account_name VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_business_wallets_business (business_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_intents (
    id CHAR(36) NOT NULL PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    order_id CHAR(36) NULL,
    offering_id CHAR(36) NULL,
    customer_id CHAR(36) NULL,
    conversation_id CHAR(36) NULL,
    amount DECIMAL(12, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    description VARCHAR(255) NULL,
    flw_tx_ref VARCHAR(255) NOT NULL,
    pay_url VARCHAR(512) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    flw_tx_id VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_payment_intents_tx_ref (flw_tx_ref),
    INDEX idx_payment_intents_business (business_id, created_at DESC),
    INDEX idx_payment_intents_status (status),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_disputes (
    id CHAR(36) NOT NULL PRIMARY KEY,
    entity_type VARCHAR(32) NOT NULL,
    entity_id CHAR(36) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'open',
    admin_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_disputes_status (status, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_wallet_payouts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    wallet_id CHAR(36) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    bank_code VARCHAR(16) NOT NULL,
    bank_name VARCHAR(120) NULL,
    account_number VARCHAR(32) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    flw_transfer_id VARCHAR(64) NULL,
    flw_reference VARCHAR(128) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    failure_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_biz_wallet_payouts (business_id, created_at DESC),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES business_wallets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
