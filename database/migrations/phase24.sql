INSERT IGNORE INTO roles (id, slug, name, description) VALUES
(13, 'extension_worker', 'Extension Worker', 'Field extension worker: full access to extension program/project forms and reports');

INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(13, 1), (13, 2);

CREATE TABLE IF NOT EXISTS clusters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO clusters (id, name) VALUES (1, 'Cluster 1'), (2, 'Cluster 2'), (3, 'Cluster 3');

ALTER TABLE colleges ADD COLUMN IF NOT EXISTS cluster_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_colleges_cluster FOREIGN KEY (cluster_id) REFERENCES clusters(id) ON DELETE SET NULL;

UPDATE colleges SET cluster_id = 1 WHERE code = 'CET';
UPDATE colleges SET cluster_id = 2 WHERE code = 'CAS';
UPDATE colleges SET cluster_id = 3 WHERE code = 'CBM';
