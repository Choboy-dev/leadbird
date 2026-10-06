-- Saved cards for subscription renewals (Flutterwave tokens).
-- Run in phpMyAdmin after prior migrations.

CREATE TABLE IF NOT EXISTS user_payment_methods (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    flutterwave_token VARCHAR(255) NOT NULL,
    card_brand VARCHAR(32) NULL,
    card_last4 VARCHAR(4) NOT NULL,
    card_exp_month CHAR(2) NOT NULL,
    card_exp_year CHAR(4) NOT NULL,
    card_holder VARCHAR(255) NULL,
    is_default BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_payment_methods_user (user_id),
    INDEX idx_user_payment_methods_user_default (user_id, is_default),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
