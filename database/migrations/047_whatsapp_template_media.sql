-- Photo, video or PDF shown above a media template's message.

ALTER TABLE whatsapp_templates
    ADD COLUMN header_media_url VARCHAR(512) NULL AFTER components;
