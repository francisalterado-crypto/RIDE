-- Faculty specializations (several per user), used to find faculty by expertise
CREATE TABLE IF NOT EXISTS user_specializations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_specialization (user_id, name),
    INDEX idx_user_specializations_name (name),
    CONSTRAINT fk_user_specializations_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
