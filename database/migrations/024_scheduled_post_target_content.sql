-- Per-channel compose content (caption, hashtags, post format) on publish targets.
ALTER TABLE scheduled_post_targets
    ADD COLUMN caption TEXT NULL AFTER platform,
    ADD COLUMN hashtags TEXT NULL AFTER caption,
    ADD COLUMN post_format VARCHAR(32) NOT NULL DEFAULT 'post' AFTER hashtags;
