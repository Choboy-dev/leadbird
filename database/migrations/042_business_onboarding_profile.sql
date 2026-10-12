-- Welcome step answers (industry, website, role, team size)

ALTER TABLE businesses ADD COLUMN industry VARCHAR(60) NULL AFTER owner_phone;
ALTER TABLE businesses ADD COLUMN website VARCHAR(255) NULL AFTER industry;
ALTER TABLE businesses ADD COLUMN owner_role VARCHAR(60) NULL AFTER website;
ALTER TABLE businesses ADD COLUMN team_size VARCHAR(20) NULL AFTER owner_role;
