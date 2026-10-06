-- Publishing deletes the uploaded video to save disk, which left published video
-- posts with no preview at all. Keep a small poster frame captured at upload time.

ALTER TABLE scheduled_posts
    ADD COLUMN thumbnail_url VARCHAR(512) NULL AFTER media_storage_path;
