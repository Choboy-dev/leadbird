-- Failed renewals are retried on day 1, 3 and 5 after the due date. The plan stays
-- usable until renewal_grace_until, then the subscription is marked expired.

ALTER TABLE user_subscriptions ADD COLUMN renewal_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER current_period_end;
ALTER TABLE user_subscriptions ADD COLUMN next_renewal_attempt_at TIMESTAMP NULL DEFAULT NULL AFTER renewal_attempts;
ALTER TABLE user_subscriptions ADD COLUMN renewal_grace_until TIMESTAMP NULL DEFAULT NULL AFTER next_renewal_attempt_at;
ALTER TABLE user_subscriptions ADD COLUMN last_renewal_error VARCHAR(255) NULL DEFAULT NULL AFTER renewal_grace_until;
