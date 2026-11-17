-- Postgres only. Statements are kept on one line where they contain semicolons, because the migration runner
-- splits on a semicolon at the end of a line.

-- Fill proposals.form_type from the summary JSON of existing rows. A regex is used instead of a ::json cast so a
-- row with malformed JSON cannot fail the migration. PHP writes compact JSON ("form_type":"..."), with form_type
-- as the first key.
UPDATE proposals
SET form_type = NULLIF(substring(summary from '"form_type":"([^"]*)"'), '')
WHERE form_type IS NULL AND summary LIKE '%"form_type":"%';

-- MySQL ON UPDATE CURRENT_TIMESTAMP has no Postgres equivalent and is dropped by the SQL translator, so
-- updated_at never moved on Postgres. A BEFORE UPDATE trigger sets it instead.
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN NEW.updated_at := CURRENT_TIMESTAMP; RETURN NEW; END $$;

DROP TRIGGER IF EXISTS trg_app_settings_updated_at ON app_settings;
CREATE TRIGGER trg_app_settings_updated_at BEFORE UPDATE ON app_settings FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_extension_activities_updated_at ON extension_activities;
CREATE TRIGGER trg_extension_activities_updated_at BEFORE UPDATE ON extension_activities FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_extension_programs_updated_at ON extension_programs;
CREATE TRIGGER trg_extension_programs_updated_at BEFORE UPDATE ON extension_programs FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_extension_projects_updated_at ON extension_projects;
CREATE TRIGGER trg_extension_projects_updated_at BEFORE UPDATE ON extension_projects FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_form_templates_updated_at ON form_templates;
CREATE TRIGGER trg_form_templates_updated_at BEFORE UPDATE ON form_templates FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_highlight_slides_updated_at ON highlight_slides;
CREATE TRIGGER trg_highlight_slides_updated_at BEFORE UPDATE ON highlight_slides FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_paper_presentations_updated_at ON paper_presentations;
CREATE TRIGGER trg_paper_presentations_updated_at BEFORE UPDATE ON paper_presentations FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_progress_reports_updated_at ON progress_reports;
CREATE TRIGGER trg_progress_reports_updated_at BEFORE UPDATE ON progress_reports FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_proposals_updated_at ON proposals;
CREATE TRIGGER trg_proposals_updated_at BEFORE UPDATE ON proposals FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_published_papers_updated_at ON published_papers;
CREATE TRIGGER trg_published_papers_updated_at BEFORE UPDATE ON published_papers FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_site_posts_updated_at ON site_posts;
CREATE TRIGGER trg_site_posts_updated_at BEFORE UPDATE ON site_posts FOR EACH ROW EXECUTE FUNCTION set_updated_at();
DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- MySQL ENUM columns become plain VARCHAR(80) on Postgres, so any value was accepted. CHECK constraints restore
-- the allowed values. NOT VALID: new writes are checked, existing rows are not, so a stray old value cannot fail
-- the migration.
ALTER TABLE proposals DROP CONSTRAINT IF EXISTS chk_proposals_status;
ALTER TABLE proposals ADD CONSTRAINT chk_proposals_status CHECK (status IN ('draft', 'submitted', 'under_review', 'returned', 'approved', 'ongoing', 'completed', 'suspended')) NOT VALID;
ALTER TABLE proposals DROP CONSTRAINT IF EXISTS chk_proposals_project_type;
ALTER TABLE proposals ADD CONSTRAINT chk_proposals_project_type CHECK (project_type IN ('research', 'innovation', 'development', 'extension')) NOT VALID;
ALTER TABLE proposals DROP CONSTRAINT IF EXISTS chk_proposals_risk_level;
ALTER TABLE proposals ADD CONSTRAINT chk_proposals_risk_level CHECK (risk_level IN ('low', 'medium', 'high')) NOT VALID;
ALTER TABLE approval_actions DROP CONSTRAINT IF EXISTS chk_approval_actions_action;
ALTER TABLE approval_actions ADD CONSTRAINT chk_approval_actions_action CHECK (action IN ('approve', 'return', 'reject')) NOT VALID;
ALTER TABLE proposal_comments DROP CONSTRAINT IF EXISTS chk_proposal_comments_action;
ALTER TABLE proposal_comments ADD CONSTRAINT chk_proposal_comments_action CHECK (action IN ('comment', 'return', 'approve', 'reject')) NOT VALID;
ALTER TABLE milestones DROP CONSTRAINT IF EXISTS chk_milestones_status;
ALTER TABLE milestones ADD CONSTRAINT chk_milestones_status CHECK (status IN ('pending', 'completed', 'overdue')) NOT VALID;
ALTER TABLE progress_reports DROP CONSTRAINT IF EXISTS chk_progress_reports_status;
ALTER TABLE progress_reports ADD CONSTRAINT chk_progress_reports_status CHECK (status IN ('draft', 'submitted', 'reviewed')) NOT VALID;
ALTER TABLE progress_reports DROP CONSTRAINT IF EXISTS chk_progress_reports_type;
ALTER TABLE progress_reports ADD CONSTRAINT chk_progress_reports_type CHECK (report_type IN ('quarterly', 'annual', 'final')) NOT VALID;
ALTER TABLE proposal_coauthor_invitations DROP CONSTRAINT IF EXISTS chk_coauthor_invitations_status;
ALTER TABLE proposal_coauthor_invitations ADD CONSTRAINT chk_coauthor_invitations_status CHECK (status IN ('pending', 'accepted', 'rejected')) NOT VALID;
