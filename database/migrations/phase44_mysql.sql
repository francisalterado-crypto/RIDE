-- MySQL / MariaDB only: fill proposals.form_type from the summary JSON of existing rows.
UPDATE proposals
SET form_type = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(summary, '$.form_type')), '')
WHERE form_type IS NULL
  AND summary IS NOT NULL
  AND JSON_VALID(summary)
  AND JSON_TYPE(JSON_EXTRACT(summary, '$.form_type')) = 'STRING';
