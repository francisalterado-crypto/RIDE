-- Public site (/, /news, /journal, /conferences): RIDE news, The Palawan Scientist journal posts (issues, calls
-- for papers) and conference posts (ICWPS, RIDE Annual Conference). Managed by the RIDE admin.
CREATE TABLE IF NOT EXISTS site_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind VARCHAR(20) NOT NULL,
    series VARCHAR(20) NULL,
    title VARCHAR(255) NOT NULL,
    summary TEXT NULL,
    body TEXT NULL,
    image_path VARCHAR(500) NULL,
    link_url VARCHAR(500) NULL,
    link_label VARCHAR(80) NULL,
    event_date DATE NULL,
    event_end_date DATE NULL,
    location VARCHAR(255) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    published_at TIMESTAMP NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_site_posts_kind (kind, is_published, published_at),
    CONSTRAINT fk_site_posts_created_by
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
