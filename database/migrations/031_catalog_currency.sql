-- Merchant catalog currency (separate from Lead Bird USD billing)

ALTER TABLE business_settings
    ADD COLUMN catalog_currency CHAR(3) NOT NULL DEFAULT 'NGN' AFTER payment_method;
