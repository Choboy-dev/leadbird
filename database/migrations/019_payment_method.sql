-- Customer payment method: Lead Bird Pay vs manual bank transfer

ALTER TABLE business_settings
    ADD COLUMN payment_method VARCHAR(32) NULL DEFAULT NULL AFTER payment_instructions;
