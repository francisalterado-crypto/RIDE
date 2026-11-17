-- Database review (2026-11): code counters, a queryable form type, and indexes for the common lookups.

-- Per-year counters for project codes (scope "RIDE-2026-") and request codes (scope "REQ-2026-").
-- Replaces COUNT(*) + 1, which gives two concurrent approvals the same number. The row is locked while a code
-- is issued (Proposal::nextCodeSequence); a missing row starts from the highest number already issued.
CREATE TABLE IF NOT EXISTS code_sequences (
    scope VARCHAR(40) NOT NULL PRIMARY KEY,
    last_value INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Copy of summary.form_type, kept in sync by the Proposal model, so lists can filter by form in SQL.
-- Backfilled by phase44_mysql / phase44_pgsql.
ALTER TABLE proposals ADD COLUMN IF NOT EXISTS form_type VARCHAR(60) NULL;

CREATE INDEX IF NOT EXISTS idx_proposals_form_type ON proposals (form_type, status);
CREATE INDEX IF NOT EXISTS idx_proposals_status_step ON proposals (status, current_step);
CREATE INDEX IF NOT EXISTS idx_proposals_user_updated ON proposals (user_id, updated_at);
CREATE INDEX IF NOT EXISTS idx_proposals_college_type ON proposals (college_id, project_type, updated_at);

-- Notification bell: unread count and latest list per user, on every page.
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications (user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_user_created ON notifications (user_id, created_at);

-- Approval history and comments of one record, in time order.
CREATE INDEX IF NOT EXISTS idx_approval_actions_proposal_created ON approval_actions (proposal_id, created_at);
CREATE INDEX IF NOT EXISTS idx_proposal_comments_proposal_created ON proposal_comments (proposal_id, created_at);

-- Audit trail of one record, and the overdue milestone check.
CREATE INDEX IF NOT EXISTS idx_audit_logs_entity ON audit_logs (entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_milestones_status_due ON milestones (status, due_date);
