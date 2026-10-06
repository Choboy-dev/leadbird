CREATE TABLE IF NOT EXISTS mail_signatures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mail_signatures_user_name (user_id, name),
    KEY idx_mail_signatures_user_default (user_id, is_default),
    CONSTRAINT fk_mail_signatures_user FOREIGN KEY (user_id) REFERENCES mail_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE mail_users ADD COLUMN signature_on_new TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE mail_users ADD COLUMN signature_on_reply TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE mail_messages MODIFY COLUMN folder ENUM('inbox','starred','sent','drafts','scheduled','archive','spam','trash') NOT NULL DEFAULT 'inbox';

ALTER TABLE mail_messages ADD COLUMN scheduled_at DATETIME NULL;

ALTER TABLE mail_messages ADD KEY idx_mail_messages_scheduled (user_id, scheduled_at);
