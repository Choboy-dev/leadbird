ALTER TABLE connected_accounts
    ADD COLUMN display_name VARCHAR(255) NULL AFTER platform_account_id;
