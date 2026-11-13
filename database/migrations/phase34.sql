-- Files attached to a conducted activity, e.g. the ETR (Extension Terminal Report) documents of a training.
CREATE TABLE IF NOT EXISTS extension_activity_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id INT UNSIGNED NOT NULL,
    category VARCHAR(60) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(100) NOT NULL,
    mime_type VARCHAR(150) NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_extension_activity_documents_activity (activity_id, category),
    CONSTRAINT fk_extension_activity_documents_activity
        FOREIGN KEY (activity_id) REFERENCES extension_activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_extension_activity_documents_user
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
