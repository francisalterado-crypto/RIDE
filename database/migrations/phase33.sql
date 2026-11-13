-- Conducted activities: individual extension activities (trainings, technical advisory, linkages, outreach, technology adoption)
-- entered one at a time; the ESO quarterly reports are generated from these entries.
CREATE TABLE IF NOT EXISTS extension_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    college_id INT UNSIGNED NULL,
    activity_type VARCHAR(40) NOT NULL,
    section VARCHAR(40) NOT NULL DEFAULT '',
    activity_date DATE NULL,
    title VARCHAR(255) NOT NULL DEFAULT '',
    data TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_extension_activities_type_date (activity_type, activity_date),
    INDEX idx_extension_activities_user (user_id),
    INDEX idx_extension_activities_college (college_id),
    CONSTRAINT fk_extension_activities_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
