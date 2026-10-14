INSERT IGNORE INTO colleges (id, code, name) VALUES
(1, 'CET', 'College of Engineering and Technology'),
(2, 'CAS', 'College of Arts and Sciences'),
(3, 'CBM', 'College of Business and Management');

INSERT IGNORE INTO campuses (id, college_id, code, name) VALUES
(1, 1, 'MAIN', 'Main Campus'),
(2, 1, 'NORTH', 'North Satellite Campus'),
(3, 2, 'MAIN', 'Main Campus'),
(4, 3, 'MAIN', 'Main Campus');

INSERT IGNORE INTO roles (id, slug, name, description) VALUES
(1, 'ride_admin', 'RIDE Admin', 'System administrator'),
(2, 'vpride', 'Admin / VPRIDE', 'Grants final approval after RDO Director or ESO Director approval'),
(3, 'ride_reporter', 'RIDE Report Generator', 'Analytics and exports'),
(4, 'coordinator_research', 'Research Coordinator', 'Endorses research submissions from their college and forwards to the College Dean'),
(12, 'coordinator_extension', 'Field Extensionist', 'Endorses extension submissions from their college and forwards to the College Dean'),
(5, 'dean', 'College Dean', 'Approves endorsed submissions and forwards to the RDO Director or ESO Director'),
(6, 'faculty', 'Faculty', 'Faculty researcher and proposal submitter'),
(7, 'ethics_reviewer', 'Ethics Review Committee', 'Ethics review step'),
(8, 'external_partner', 'External Partner', 'View-only collaborator'),
(9, 'director_research', 'RDO Director', 'Approves research submissions after College Dean and forwards to VPRIDE'),
(10, 'director_extension', 'ESO Director', 'Approves extension submissions after College Dean and forwards to VPRIDE');

INSERT IGNORE INTO permissions (id, slug, name) VALUES
(1, 'proposal.create', 'Create proposals'),
(2, 'proposal.submit', 'Submit proposals'),
(3, 'proposal.review.college', 'Review at college level'),
(4, 'proposal.approve.dean', 'Dean approval'),
(5, 'proposal.approve.ethics', 'Ethics approval'),
(6, 'proposal.approve.ride', 'RIDE final approval'),
(7, 'proposal.view.college', 'View college proposals'),
(8, 'proposal.view.all', 'View all proposals'),
(9, 'admin.manage', 'Administration'),
(10, 'report.export', 'Export reports');

INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(1, 1),(1, 2),(1, 3),(1, 4),(1, 5),(1, 6),(1, 7),(1, 8),(1, 9),(1, 10),
(2, 3),(2, 6),(2, 7),(2, 8),(2, 10),
(3, 8),(3, 10),
(4, 3),(4, 7),
(12, 3),(12, 7),
(5, 4),(5, 7),
(6, 1),(6, 2),
(7, 5),
(8, 7),
(9, 3),(9, 6),(9, 7),(9, 8),(9, 10),
(10, 3),(10, 6),(10, 7),(10, 8),(10, 10);

-- Default password for demo admin: password123
INSERT IGNORE INTO users (id, email, password_hash, first_name, last_name, college_id, campus_id) VALUES
(1, 'admin@ride.local', '$2y$10$1UFSLUmOVn4oH5uXD3thxOOGUJ68FZadbLaN18VrdfvrR1u1.RjNi', 'Ana', 'Reyes', NULL, NULL);

INSERT IGNORE INTO user_roles (user_id, role_id, college_id) VALUES
(1, 1, NULL);
