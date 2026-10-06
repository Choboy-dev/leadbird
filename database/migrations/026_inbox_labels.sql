-- Inbox labels and conversation grouping

CREATE TABLE inbox_labels (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_inbox_label_business_slug (business_id, slug),
    INDEX idx_inbox_labels_business (business_id)
);

CREATE TABLE inbox_conversation_labels (
    conversation_id CHAR(36) NOT NULL,
    label_id CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (conversation_id, label_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (label_id) REFERENCES inbox_labels(id) ON DELETE CASCADE,
    INDEX idx_inbox_conv_labels_label (label_id)
);
