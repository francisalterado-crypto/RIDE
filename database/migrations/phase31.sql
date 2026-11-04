-- Records whether the filer was the coordinator at submission, so the workflow display no longer follows later role changes.
-- NULL means not submitted since this column was added; the display then falls back to the filer's current roles.
ALTER TABLE proposals ADD COLUMN IF NOT EXISTS coordinator_filed TINYINT(1) NULL;

-- A record waiting at a coordinator step cannot have been filed by that coordinator.
UPDATE proposals SET coordinator_filed = 0
WHERE status IN ('submitted', 'under_review')
  AND current_step IN ('coordinator_research', 'coordinator_extension');
