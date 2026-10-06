-- Type-specific offering fields, Google Calendar, and consultation bookings

ALTER TABLE offerings
    ADD COLUMN is_free TINYINT(1) NOT NULL DEFAULT 0 AFTER price,
    ADD COLUMN action_url VARCHAR(512) NULL AFTER is_free,
    ADD COLUMN type_config JSON NULL AFTER action_url;

CREATE TABLE business_google_calendar (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    google_email VARCHAR(255) NULL,
    calendar_id VARCHAR(255) NOT NULL DEFAULT 'primary',
    access_token TEXT NOT NULL,
    refresh_token TEXT NULL,
    token_expires_at TIMESTAMP NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Africa/Lagos',
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_bgc_business (business_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

CREATE TABLE consultation_appointments (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    offering_id CHAR(36) NOT NULL,
    conversation_id CHAR(36) NULL,
    customer_id CHAR(36) NULL,
    customer_name VARCHAR(255) NULL,
    customer_phone VARCHAR(50) NULL,
    customer_email VARCHAR(255) NULL,
    scheduled_start TIMESTAMP NOT NULL,
    scheduled_end TIMESTAMP NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'confirmed',
    google_event_id VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (offering_id) REFERENCES offerings(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_ca_business (business_id),
    INDEX idx_ca_offering (offering_id),
    INDEX idx_ca_conversation (conversation_id),
    INDEX idx_ca_start (scheduled_start)
);

CREATE TABLE conversation_booking_state (
    id CHAR(36) PRIMARY KEY,
    conversation_id CHAR(36) NOT NULL,
    offering_id CHAR(36) NOT NULL,
    step VARCHAR(50) NOT NULL,
    collected_data JSON NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cbs_conversation (conversation_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (offering_id) REFERENCES offerings(id) ON DELETE CASCADE
);
