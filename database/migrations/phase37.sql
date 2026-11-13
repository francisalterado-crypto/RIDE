-- Progress report review: in-scope approvers mark a submitted report reviewed or return it to draft with a comment.
ALTER TABLE progress_reports
    ADD COLUMN IF NOT EXISTS review_comment TEXT NULL;

ALTER TABLE progress_reports
    ADD COLUMN IF NOT EXISTS reviewed_by INT UNSIGNED NULL;

ALTER TABLE progress_reports
    ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL;
