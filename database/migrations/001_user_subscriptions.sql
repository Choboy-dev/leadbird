-- Lead Bird SaaS subscriptions (Flutterwave recurring billing)
-- Run in phpMyAdmin after the base schema.

CREATE TABLE IF NOT EXISTS user_subscriptions (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    plan_slug VARCHAR(50) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    amount DECIMAL(12, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    flutterwave_tx_ref VARCHAR(255) NULL,
    flutterwave_tx_id VARCHAR(64) NULL,
    flutterwave_payment_plan_id VARCHAR(64) NULL,
    flutterwave_subscription_id VARCHAR(64) NULL,
    current_period_start TIMESTAMP NULL,
    current_period_end TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    meta JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_subscriptions_tx_ref (flutterwave_tx_ref),
    INDEX idx_user_subscriptions_user_status (user_id, status),
    INDEX idx_user_subscriptions_user_created (user_id, created_at DESC),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE businesses
    ADD COLUMN subscription_status VARCHAR(32) DEFAULT 'inactive' AFTER subscription_plan,
    ADD COLUMN subscription_expires_at TIMESTAMP NULL AFTER subscription_status;
