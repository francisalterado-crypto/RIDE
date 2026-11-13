-- Program catalog: academic programs, extension programs, extension projects and their planned activities,
-- replacing free-text program names. Clusters get a track so research (RDO) and extension (ESO) groupings
-- are defined separately; a college/center can sit in one cluster of each track.

ALTER TABLE clusters ADD COLUMN IF NOT EXISTS track VARCHAR(20) NOT NULL DEFAULT 'extension';

ALTER TABLE colleges ADD COLUMN IF NOT EXISTS research_cluster_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_colleges_research_cluster FOREIGN KEY (research_cluster_id) REFERENCES clusters(id) ON DELETE SET NULL;

ALTER TABLE colleges ADD COLUMN IF NOT EXISTS extension_cluster_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_colleges_extension_cluster FOREIGN KEY (extension_cluster_id) REFERENCES clusters(id) ON DELETE SET NULL;

UPDATE colleges SET extension_cluster_id = cluster_id WHERE cluster_id IS NOT NULL AND extension_cluster_id IS NULL;

CREATE TABLE IF NOT EXISTS academic_programs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_academic_programs_name (name),
    INDEX idx_academic_programs_college (college_id),
    CONSTRAINT fk_academic_programs_college
        FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS extension_programs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_id INT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    acronym VARCHAR(40) NULL,
    description TEXT NULL,
    agenda_period VARCHAR(20) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_extension_programs_college_name (college_id, name),
    CONSTRAINT fk_extension_programs_college
        FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS extension_projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    extension_program_id INT UNSIGNED NOT NULL,
    college_id INT UNSIGNED NULL,
    academic_program_id INT UNSIGNED NULL,
    campus VARCHAR(80) NULL,
    title VARCHAR(500) NOT NULL,
    leader_name VARCHAR(255) NULL,
    leader_user_id INT UNSIGNED NULL,
    year SMALLINT NULL,
    total_budget DECIMAL(14,2) NULL,
    target_participants INT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'planned',
    source VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_extension_projects_program (extension_program_id),
    INDEX idx_extension_projects_college_year (college_id, year),
    CONSTRAINT fk_extension_projects_program
        FOREIGN KEY (extension_program_id) REFERENCES extension_programs(id) ON DELETE CASCADE,
    CONSTRAINT fk_extension_projects_college
        FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL,
    CONSTRAINT fk_extension_projects_academic_program
        FOREIGN KEY (academic_program_id) REFERENCES academic_programs(id) ON DELETE SET NULL,
    CONSTRAINT fk_extension_projects_leader
        FOREIGN KEY (leader_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS extension_project_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    extension_project_id INT UNSIGNED NOT NULL,
    title VARCHAR(500) NOT NULL,
    coordinators TEXT NULL,
    participants INT UNSIGNED NULL,
    budget DECIMAL(14,2) NULL,
    schedule VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_extension_project_activities_project (extension_project_id, sort_order),
    CONSTRAINT fk_extension_project_activities_project
        FOREIGN KEY (extension_project_id) REFERENCES extension_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users ADD COLUMN IF NOT EXISTS academic_program_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_users_academic_program FOREIGN KEY (academic_program_id) REFERENCES academic_programs(id) ON DELETE SET NULL;

ALTER TABLE proposals ADD COLUMN IF NOT EXISTS academic_program_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_proposals_academic_program FOREIGN KEY (academic_program_id) REFERENCES academic_programs(id) ON DELETE SET NULL;

ALTER TABLE proposals ADD COLUMN IF NOT EXISTS extension_program_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_proposals_extension_program FOREIGN KEY (extension_program_id) REFERENCES extension_programs(id) ON DELETE SET NULL;

ALTER TABLE proposals ADD COLUMN IF NOT EXISTS extension_project_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_proposals_extension_project FOREIGN KEY (extension_project_id) REFERENCES extension_projects(id) ON DELETE SET NULL;

ALTER TABLE extension_activities ADD COLUMN IF NOT EXISTS extension_project_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_extension_activities_project FOREIGN KEY (extension_project_id) REFERENCES extension_projects(id) ON DELETE SET NULL;
