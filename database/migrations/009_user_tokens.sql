CREATE TABLE user_token_balances (
    user_id CHAR(36) PRIMARY KEY,
    balance INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_token_ledger (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    delta INT NOT NULL,
    balance_after INT UNSIGNED NOT NULL,
    reason VARCHAR(64) NOT NULL,
    reference_type VARCHAR(32) NULL,
    reference_id VARCHAR(64) NULL,
    meta JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token_ledger_user_created (user_id, created_at DESC),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE token_topup_orders (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    package_slug VARCHAR(32) NOT NULL,
    tokens INT UNSIGNED NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    flutterwave_tx_ref VARCHAR(255) NOT NULL,
    flutterwave_tx_id VARCHAR(64) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token_topup_tx_ref (flutterwave_tx_ref),
    INDEX idx_token_topup_user_created (user_id, created_at DESC),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
