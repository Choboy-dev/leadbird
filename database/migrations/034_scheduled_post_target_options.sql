-- Per-target publish settings (TikTok privacy level, comment/duet/stitch permissions) as JSON.

ALTER TABLE scheduled_post_targets
    ADD COLUMN options TEXT NULL AFTER post_format;
