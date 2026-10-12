-- Templates page: every template on the WhatsApp Business Account, including ones made outside Lead Bird.

ALTER TABLE whatsapp_templates
    ADD COLUMN content_type VARCHAR(32) NOT NULL DEFAULT 'text' AFTER category,
    ADD COLUMN components MEDIUMTEXT NULL AFTER footer,
    ADD COLUMN quality VARCHAR(16) NULL AFTER status,
    ADD COLUMN source VARCHAR(16) NOT NULL DEFAULT 'leadbird' AFTER quality,
    ADD COLUMN deleted_at TIMESTAMP NULL AFTER rejected_reason;

ALTER TABLE whatsapp_templates
    ADD INDEX idx_wa_templates_live (business_id, deleted_at, updated_at);
