-- AI profile tone and greeting for dashboard editing.
-- Run after 005_user_billing_profiles.sql.

ALTER TABLE business_settings
    ADD COLUMN ai_tone VARCHAR(255) NOT NULL DEFAULT 'Friendly and helpful' AFTER business_description;

ALTER TABLE business_settings
    ADD COLUMN ai_greeting VARCHAR(500) NOT NULL DEFAULT 'Hi! How can I help you today?' AFTER ai_tone;
