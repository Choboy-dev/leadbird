-- Answers from the new onboarding flow (channels, WhatsApp history, Meta account)

ALTER TABLE businesses ADD COLUMN onboarding_answers JSON NULL AFTER team_size;
