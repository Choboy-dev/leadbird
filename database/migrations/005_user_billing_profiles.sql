-- Billing address required by payment provider for card charges.
-- Run in phpMyAdmin after 004_user_payment_methods.sql.

CREATE TABLE IF NOT EXISTS user_billing_profiles (
    user_id CHAR(36) PRIMARY KEY,
    address_line1 VARCHAR(255) NOT NULL,
    city VARCHAR(120) NOT NULL,
    state VARCHAR(120) NOT NULL,
    country CHAR(2) NOT NULL DEFAULT 'NG',
    postal_code VARCHAR(20) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
