ALTER TABLE businesses ADD INDEX idx_businesses_user_id (user_id);
ALTER TABLE businesses DROP INDEX uk_businesses_user_id;
