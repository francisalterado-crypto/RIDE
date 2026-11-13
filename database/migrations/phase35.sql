-- Proponent details asked by the RDO forms (Research Proposal Application, request forms). They live on the
-- user profile so every form fills them in instead of asking again.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS middle_name VARCHAR(80) NULL AFTER last_name,
    ADD COLUMN IF NOT EXISTS title_prefix VARCHAR(40) NULL AFTER middle_name,
    ADD COLUMN IF NOT EXISTS sex VARCHAR(10) NULL AFTER title_prefix,
    ADD COLUMN IF NOT EXISTS position VARCHAR(120) NULL AFTER sex,
    ADD COLUMN IF NOT EXISTS contact_number VARCHAR(30) NULL AFTER position;
