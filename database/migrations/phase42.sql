-- Role model (2026-11): college roles, RIDE-side coordinators linked to clusters, Administrator apart from OVPRIDE.
-- Roles: Faculty; College Coordinator - Research / - Extension (faculty members, one college); College Dean;
-- Research Coordinator (RIDE-RDO) and Field Extensionist (RIDE-ESO), each linked to clusters of their track;
-- RDO Director; ESO Director; OVPRIDE; Administrator. Extension Worker, Reporter, Ethics Reviewer and External
-- Partner are removed.

INSERT INTO roles (slug, name, description)
SELECT 'rdo_coordinator', 'Research Coordinator (RIDE-RDO)', 'Reviews research proposals and requests of the colleges in their research clusters before the RDO Director, and prepares the RDO quarterly reports.'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'rdo_coordinator');

INSERT INTO roles (slug, name, description)
SELECT 'field_extensionist', 'Field Extensionist (RIDE-ESO)', 'Reviews extension proposals of the colleges in their extension clusters before the ESO Director, and prepares the ESO quarterly reports.'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'field_extensionist');

UPDATE roles SET name = 'College Coordinator - Research', description = 'Faculty member who endorses the research proposals, requests and reports of the college.' WHERE slug = 'coordinator_research';
UPDATE roles SET name = 'College Coordinator - Extension', description = 'Faculty member who endorses the extension proposals and reports of the college.' WHERE slug = 'coordinator_extension';
UPDATE roles SET name = 'College Dean' WHERE slug = 'dean';
UPDATE roles SET name = 'OVPRIDE', description = 'Office of the Vice President for RIDE: final approval.' WHERE slug = 'vpride';
UPDATE roles SET name = 'Administrator', description = 'Access to everything and the system configuration. Not part of the approval chain.' WHERE slug = 'ride_admin';

-- Permissions of the new roles: the same as the college roles they replace for viewing and reviewing.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('proposal.view.college', 'proposal.review.college', 'report.export')
WHERE r.slug IN ('rdo_coordinator', 'field_extensionist');

-- RIDE coordinators cover one or more clusters of their track.
CREATE TABLE IF NOT EXISTS user_clusters (
    user_id INT UNSIGNED NOT NULL,
    cluster_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, cluster_id),
    CONSTRAINT user_clusters_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT user_clusters_cluster_fk FOREIGN KEY (cluster_id) REFERENCES clusters (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extension workers become Field Extensionists, linked to the extension cluster of their college.
INSERT IGNORE INTO user_roles (user_id, role_id, college_id)
SELECT ur.user_id, (SELECT id FROM roles WHERE slug = 'field_extensionist'), NULL
FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE r.slug = 'extension_worker';

INSERT IGNORE INTO user_clusters (user_id, cluster_id)
SELECT u.id, c.extension_cluster_id
FROM users u
JOIN user_roles ur ON ur.user_id = u.id
JOIN roles r ON r.id = ur.role_id AND r.slug = 'extension_worker'
JOIN colleges c ON c.id = u.college_id
WHERE c.extension_cluster_id IS NOT NULL;

-- College coordinators are faculty members.
INSERT IGNORE INTO user_roles (user_id, role_id, college_id)
SELECT ur.user_id, (SELECT id FROM roles WHERE slug = 'faculty'), ur.college_id
FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE r.slug IN ('coordinator_research', 'coordinator_extension');

-- Remove the dropped roles (their user_roles and role_permissions rows go with them).
DELETE FROM roles WHERE slug IN ('extension_worker', 'ride_reporter', 'ethics_reviewer', 'external_partner');
