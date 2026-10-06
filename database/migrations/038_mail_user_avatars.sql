-- Profile pictures for mailboxes, shown inside Lead Bird Mail. Sender avatars in
-- external inboxes need BIMI, which is a DNS and certificate change instead.

ALTER TABLE mail_users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER avatar_color;
