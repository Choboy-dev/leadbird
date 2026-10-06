-- Facebook Page ID used for Instagram DM send API (/{page-id}/messages)

ALTER TABLE connected_accounts
    ADD COLUMN linked_page_id VARCHAR(64) NULL AFTER platform_account_id;
