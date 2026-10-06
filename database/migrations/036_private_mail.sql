CREATE TABLE IF NOT EXISTS mail_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    display_name VARCHAR(190) NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    avatar_color VARCHAR(32) NOT NULL DEFAULT '#0038FF',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mail_users_email (email),
    KEY idx_mail_users_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    folder ENUM('inbox','starred','sent','drafts','archive','spam','trash') NOT NULL DEFAULT 'inbox',
    category ENUM('primary','promotions','social','updates') NOT NULL DEFAULT 'primary',
    direction ENUM('inbound','outbound') NOT NULL DEFAULT 'inbound',
    from_email VARCHAR(254) NOT NULL,
    from_name VARCHAR(190) NULL,
    to_emails JSON NOT NULL,
    cc_emails JSON NULL,
    bcc_emails JSON NULL,
    subject VARCHAR(998) NOT NULL DEFAULT '',
    body_html MEDIUMTEXT NULL,
    body_text MEDIUMTEXT NULL,
    snippet VARCHAR(500) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_starred TINYINT(1) NOT NULL DEFAULT 0,
    thread_id VARCHAR(255) NULL,
    message_id VARCHAR(255) NULL,
    in_reply_to VARCHAR(255) NULL,
    resend_email_id VARCHAR(255) NULL,
    sent_at DATETIME NULL,
    received_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mail_messages_resend_email_id (resend_email_id),
    KEY idx_mail_messages_user_folder_created (user_id, folder, created_at),
    KEY idx_mail_messages_user_starred (user_id, is_starred),
    KEY idx_mail_messages_thread (thread_id),
    CONSTRAINT fk_mail_messages_user FOREIGN KEY (user_id) REFERENCES mail_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id BIGINT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    content_type VARCHAR(190) NOT NULL DEFAULT 'application/octet-stream',
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    storage_path VARCHAR(500) NOT NULL,
    resend_attachment_id VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mail_attachments_message (message_id),
    CONSTRAINT fk_mail_attachments_message FOREIGN KEY (message_id) REFERENCES mail_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_labels (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(32) NOT NULL DEFAULT '#0038FF',
    PRIMARY KEY (id),
    UNIQUE KEY uq_mail_labels_user_name (user_id, name),
    CONSTRAINT fk_mail_labels_user FOREIGN KEY (user_id) REFERENCES mail_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_message_labels (
    message_id BIGINT UNSIGNED NOT NULL,
    label_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (message_id, label_id),
    CONSTRAINT fk_mail_message_labels_message FOREIGN KEY (message_id) REFERENCES mail_messages(id) ON DELETE CASCADE,
    CONSTRAINT fk_mail_message_labels_label FOREIGN KEY (label_id) REFERENCES mail_labels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
