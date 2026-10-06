-- TikTok tokens expire (access token ~24h, refresh token ~365 days), so store expiry + granted scopes.

ALTER TABLE connected_accounts
    ADD COLUMN token_expires_at DATETIME NULL AFTER refresh_token;

ALTER TABLE connected_accounts
    ADD COLUMN refresh_expires_at DATETIME NULL AFTER token_expires_at;

ALTER TABLE connected_accounts
    ADD COLUMN scopes TEXT NULL AFTER refresh_expires_at;
