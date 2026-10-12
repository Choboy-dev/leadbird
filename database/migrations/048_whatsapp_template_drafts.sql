-- Templates saved in Lead Bird but not yet sent to WhatsApp (status DRAFT).

ALTER TABLE whatsapp_templates
    ADD COLUMN draft_spec MEDIUMTEXT NULL AFTER header_media_url;
