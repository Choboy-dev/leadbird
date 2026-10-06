-- One-time backfill: align businesses with active user_subscriptions.
-- Safe to run multiple times.

UPDATE businesses b
INNER JOIN user_subscriptions s
    ON s.user_id = b.user_id
    AND s.status = 'active'
    AND (s.current_period_end IS NULL OR s.current_period_end > NOW())
SET
    b.subscription_plan = s.plan_slug,
    b.subscription_status = 'active',
    b.subscription_expires_at = s.current_period_end,
    b.updated_at = NOW()
WHERE b.subscription_status <> 'active'
   OR b.subscription_plan <> s.plan_slug
   OR b.subscription_expires_at IS NULL
   OR b.subscription_expires_at <> s.current_period_end;
