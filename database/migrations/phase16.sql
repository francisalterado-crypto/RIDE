-- Separate research and extension coordinator roles at college level

INSERT IGNORE INTO roles (id, slug, name, description) VALUES
(11, 'coordinator_research', 'Coordinator of Research', 'Endorses research submissions from their college and forwards to the College Dean'),
(12, 'coordinator_extension', 'Coordinator of Extension', 'Endorses extension submissions from their college and forwards to the College Dean');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, 3 FROM roles r WHERE r.slug = 'coordinator_research';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, 7 FROM roles r WHERE r.slug = 'coordinator_research';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, 3 FROM roles r WHERE r.slug = 'coordinator_extension';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, 7 FROM roles r WHERE r.slug = 'coordinator_extension';

UPDATE user_roles ur
INNER JOIN roles r ON r.id = ur.role_id AND r.slug = 'college_coordinator'
SET ur.role_id = (SELECT id FROM roles WHERE slug = 'coordinator_research' LIMIT 1);

UPDATE proposals SET current_step = 'coordinator_research'
WHERE current_step = 'college_coordinator' AND project_type != 'extension';

UPDATE proposals SET current_step = 'coordinator_extension'
WHERE current_step = 'college_coordinator' AND project_type = 'extension';

UPDATE approval_actions aa
INNER JOIN proposals p ON p.id = aa.proposal_id
SET aa.step = CASE WHEN p.project_type = 'extension' THEN 'coordinator_extension' ELSE 'coordinator_research' END
WHERE aa.step = 'college_coordinator';

UPDATE proposal_comments pc
INNER JOIN proposals p ON p.id = pc.proposal_id
SET pc.step = CASE WHEN p.project_type = 'extension' THEN 'coordinator_extension' ELSE 'coordinator_research' END
WHERE pc.step = 'college_coordinator';

DELETE FROM role_permissions WHERE role_id = (SELECT id FROM roles WHERE slug = 'college_coordinator' LIMIT 1);
DELETE FROM roles WHERE slug = 'college_coordinator';
