-- Researcher identifiers on the user profile (ORCID iD, Google Scholar, ResearchGate, GitHub)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS orcid_id VARCHAR(19) NULL AFTER program,
    ADD COLUMN IF NOT EXISTS google_scholar_url VARCHAR(255) NULL AFTER orcid_id,
    ADD COLUMN IF NOT EXISTS researchgate_url VARCHAR(255) NULL AFTER google_scholar_url,
    ADD COLUMN IF NOT EXISTS github_url VARCHAR(255) NULL AFTER researchgate_url;
