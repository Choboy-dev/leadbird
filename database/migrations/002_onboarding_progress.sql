-- Track onboarding wizard progress per business.
-- Run in phpMyAdmin after 001_user_subscriptions.sql (or on existing installs).

ALTER TABLE businesses
    ADD COLUMN onboarding_step TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER subscription_expires_at,
    ADD COLUMN onboarding_completed_at TIMESTAMP NULL AFTER onboarding_step;
