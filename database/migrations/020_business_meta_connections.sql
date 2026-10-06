-- Business-level Meta user token (OAuth once, add channels in-app)

CREATE TABLE business_meta_connections (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    meta_user_id VARCHAR(64) NULL,
    access_token TEXT NOT NULL,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_bmc_business (business_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);
