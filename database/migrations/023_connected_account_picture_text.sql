ALTER TABLE connected_accounts
    MODIFY COLUMN profile_picture_url TEXT NULL;

UPDATE connected_accounts
SET profile_picture_url = NULL
WHERE profile_picture_url IS NOT NULL
  AND CHAR_LENGTH(profile_picture_url) >= 512;
