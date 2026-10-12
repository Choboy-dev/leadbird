-- Partner payouts: admin review before money leaves, and commissions tied to the payout that reserves them

ALTER TABLE affiliate_commissions
    ADD COLUMN payout_id CHAR(36) NULL AFTER subscription_id,
    ADD INDEX idx_commission_payout (payout_id);

ALTER TABLE affiliate_payouts
    ADD COLUMN amount_usd DECIMAL(12, 2) NULL AFTER amount,
    ADD COLUMN reviewed_by CHAR(36) NULL AFTER failure_reason,
    ADD COLUMN reviewed_at TIMESTAMP NULL AFTER reviewed_by,
    ADD INDEX idx_payouts_status (status, created_at DESC),
    ADD INDEX idx_payouts_reference (flw_reference);
