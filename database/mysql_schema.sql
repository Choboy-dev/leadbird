-- AI Sales Girl — Hostinger MySQL schema
-- Run in phpMyAdmin (select your database → SQL tab).

-- ---------------------------------------------------------------------------
-- Auth & identity
-- ---------------------------------------------------------------------------

CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NULL,
    google_id VARCHAR(255) NULL,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_users_email (email),
    UNIQUE KEY uk_users_google_id (google_id)
);

CREATE TABLE email_otps (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_otps_email_created (email, created_at DESC)
);

CREATE TABLE oauth_states (
    id CHAR(36) PRIMARY KEY,
    state VARCHAR(64) NOT NULL,
    code_verifier VARCHAR(128) NOT NULL,
    context TEXT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_oauth_states_state (state)
);

-- ---------------------------------------------------------------------------
-- Business & onboarding
-- ---------------------------------------------------------------------------

CREATE TABLE businesses (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    owner_name VARCHAR(255) NULL,
    owner_email VARCHAR(255) NULL,
    owner_phone VARCHAR(50) NULL,
    subscription_plan VARCHAR(50) DEFAULT 'free',
    subscription_status VARCHAR(32) DEFAULT 'inactive',
    subscription_expires_at TIMESTAMP NULL,
    onboarding_step TINYINT UNSIGNED NOT NULL DEFAULT 1,
    onboarding_completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_businesses_user_id (user_id),
    INDEX idx_businesses_owner_email (owner_email),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_subscriptions (
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
    renewal_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    next_renewal_attempt_at TIMESTAMP NULL DEFAULT NULL,
    renewal_grace_until TIMESTAMP NULL DEFAULT NULL,
    last_renewal_error VARCHAR(255) NULL DEFAULT NULL,
    paid_at TIMESTAMP NULL,
    meta JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_subscriptions_tx_ref (flutterwave_tx_ref),
    INDEX idx_user_subscriptions_user_status (user_id, status),
    INDEX idx_user_subscriptions_user_created (user_id, created_at DESC),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_payment_methods (
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

CREATE TABLE user_billing_profiles (
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

CREATE TABLE connected_accounts (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    platform_account_id VARCHAR(255) NULL,
    linked_page_id VARCHAR(64) NULL,
    display_name VARCHAR(255) NULL,
    profile_picture_url TEXT NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at DATETIME NULL,
    refresh_expires_at DATETIME NULL,
    scopes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_connected_accounts_business (business_id),
    INDEX idx_connected_accounts_platform_account (platform, platform_account_id)
);

CREATE TABLE scheduled_posts (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    created_by_user_id CHAR(36) NULL,
    caption TEXT NOT NULL,
    hashtags TEXT NULL,
    media_type VARCHAR(32) NOT NULL DEFAULT 'image',
    media_url VARCHAR(512) NULL,
    media_storage_path VARCHAR(512) NULL,
    thumbnail_url VARCHAR(512) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    ai_assisted TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_scheduled_posts_business (business_id, created_at DESC),
    INDEX idx_scheduled_posts_due (status, scheduled_at),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

CREATE TABLE scheduled_post_targets (
    id CHAR(36) PRIMARY KEY,
    scheduled_post_id CHAR(36) NOT NULL,
    connected_account_id CHAR(36) NOT NULL,
    platform VARCHAR(32) NOT NULL,
    caption TEXT NULL,
    hashtags TEXT NULL,
    post_format VARCHAR(32) NOT NULL DEFAULT 'post',
    options TEXT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    platform_post_id VARCHAR(255) NULL,
    platform_permalink VARCHAR(512) NULL,
    error_message TEXT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_scheduled_post_targets_post (scheduled_post_id),
    INDEX idx_scheduled_post_targets_status (status),
    FOREIGN KEY (scheduled_post_id) REFERENCES scheduled_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (connected_account_id) REFERENCES connected_accounts(id) ON DELETE CASCADE
);

CREATE TABLE scheduled_post_media (
    id CHAR(36) PRIMARY KEY,
    scheduled_post_id CHAR(36) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    media_type VARCHAR(32) NOT NULL DEFAULT 'image',
    media_url VARCHAR(512) NOT NULL,
    media_storage_path VARCHAR(512) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scheduled_post_media_post (scheduled_post_id, sort_order),
    FOREIGN KEY (scheduled_post_id) REFERENCES scheduled_posts(id) ON DELETE CASCADE
);

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

CREATE TABLE business_settings (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    ai_name VARCHAR(100) DEFAULT 'Sales Girl',
    business_description TEXT NULL,
    ai_tone VARCHAR(255) NOT NULL DEFAULT 'Friendly and helpful',
    ai_greeting VARCHAR(500) NOT NULL DEFAULT 'Hi! How can I help you today?',
    faq TEXT NULL,
    delivery_policy TEXT NULL,
    return_policy TEXT NULL,
    payment_instructions TEXT NULL,
    payment_method VARCHAR(32) NULL,
    escalation_phone VARCHAR(50) NULL,
    escalation_email VARCHAR(255) NULL,
    handoff_to_whatsapp BOOLEAN DEFAULT TRUE,
    notify_owner_on_hot_lead BOOLEAN DEFAULT TRUE,
    notify_owner_on_payment BOOLEAN DEFAULT TRUE,
    notify_owner_on_complaint BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_business_settings_business (business_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

CREATE TABLE offerings (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    offering_type VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_url VARCHAR(512) NULL,
    price DECIMAL(12, 2) NULL,
    currency VARCHAR(10) DEFAULT 'NGN',
    billing_type VARCHAR(100) DEFAULT 'one_time',
    stock_quantity INT NULL,
    capacity INT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_offerings_business (business_id)
);

CREATE TABLE business_knowledge_chunks (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    source_type VARCHAR(50) NOT NULL,
    source_id VARCHAR(255) NULL,
    title VARCHAR(255) NULL,
    content TEXT NOT NULL,
    keywords TEXT NULL,
    image_url VARCHAR(512) NULL,
    metadata TEXT NULL,
    embedding_json MEDIUMTEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bkc_business (business_id),
    INDEX idx_bkc_business_source (business_id, source_type),
    INDEX idx_bkc_business_source_id (business_id, source_type, source_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

CREATE TABLE business_knowledge_sync (
    business_id CHAR(36) PRIMARY KEY,
    last_manual_sync_at TIMESTAMP NULL,
    last_social_sync_at TIMESTAMP NULL,
    last_embedding_sync_at TIMESTAMP NULL,
    last_learning_sync_at TIMESTAMP NULL,
    social_sync_status VARCHAR(32) NOT NULL DEFAULT 'idle',
    social_post_count INT NOT NULL DEFAULT 0,
    embedded_chunk_count INT NOT NULL DEFAULT 0,
    learning_memory_count INT NOT NULL DEFAULT 0,
    linked_post_count INT NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

CREATE TABLE business_knowledge_links (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    social_chunk_id CHAR(36) NOT NULL,
    offering_id CHAR(36) NOT NULL,
    match_score DECIMAL(5, 4) NULL,
    link_source VARCHAR(20) NOT NULL DEFAULT 'auto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_bkl_social_offering (social_chunk_id, offering_id),
    INDEX idx_bkl_business (business_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (social_chunk_id) REFERENCES business_knowledge_chunks(id) ON DELETE CASCADE,
    FOREIGN KEY (offering_id) REFERENCES offerings(id) ON DELETE CASCADE
);

CREATE TABLE products (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(12, 2) NULL,
    currency VARCHAR(10) DEFAULT 'NGN',
    stock_quantity INT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_products_business (business_id)
);

-- ---------------------------------------------------------------------------
-- CRM, chat, commerce
-- ---------------------------------------------------------------------------

CREATE TABLE customers (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    customer_name VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    platform VARCHAR(50) NULL,
    platform_user_id VARCHAR(255) NULL,
    profile_picture_url TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_customers_business (business_id)
);

CREATE TABLE conversations (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NULL,
    platform VARCHAR(50) NULL,
    status VARCHAR(50) DEFAULT 'open',
    last_message TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_conversations_business (business_id)
);

CREATE TABLE messages (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NULL,
    conversation_id CHAR(36) NULL,
    platform VARCHAR(50) NOT NULL,
    sender_type VARCHAR(50) NOT NULL,
    message_text TEXT NULL,
    message_media_url VARCHAR(512) NULL,
    message_media_type VARCHAR(32) NULL,
    referral_context TEXT NULL,
    ai_intent VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    INDEX idx_messages_conversation (conversation_id)
);

CREATE TABLE orders (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NULL,
    conversation_id CHAR(36) NULL,
    status VARCHAR(50) DEFAULT 'pending',
    total_amount DECIMAL(12, 2) NULL,
    currency VARCHAR(10) DEFAULT 'NGN',
    delivery_location TEXT NULL,
    customer_phone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
    INDEX idx_orders_business (business_id)
);

CREATE TABLE order_items (
    id CHAR(36) PRIMARY KEY,
    order_id CHAR(36) NOT NULL,
    product_id CHAR(36) NULL,
    offering_id CHAR(36) NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(12, 2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (offering_id) REFERENCES offerings(id) ON DELETE SET NULL,
    INDEX idx_order_items_order (order_id)
);

CREATE TABLE payments (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    order_id CHAR(36) NOT NULL,
    amount DECIMAL(12, 2) NULL,
    payment_method VARCHAR(100) NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    proof_url TEXT NULL,
    transaction_reference VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_payments_order (order_id)
);

CREATE TABLE owner_alerts (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NULL,
    conversation_id CHAR(36) NULL,
    platform VARCHAR(50) NULL,
    alert_reason TEXT NULL,
    alert_message TEXT NULL,
    status VARCHAR(50) DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
    INDEX idx_owner_alerts_business (business_id)
);

CREATE TABLE handoffs (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NULL,
    from_platform VARCHAR(50) NULL,
    to_platform VARCHAR(50) DEFAULT 'whatsapp',
    handoff_reason TEXT NULL,
    handoff_link TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_handoffs_business (business_id)
);

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

CREATE TABLE openai_usage_events (
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
