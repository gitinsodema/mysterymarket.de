-- MysteryMarket R1.1 agency master data

CREATE TABLE IF NOT EXISTS agencies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    short_name VARCHAR(120) NULL,
    website_url VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_agencies_name (name),
    KEY idx_agencies_active_name (is_active, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE elite_feed_posts
    ADD COLUMN IF NOT EXISTS agency_id BIGINT UNSIGNED NULL AFTER body,
    ADD KEY IF NOT EXISTS idx_elite_feed_agency_id (agency_id);

ALTER TABLE agency_approvals
    ADD COLUMN IF NOT EXISTS agency_id BIGINT UNSIGNED NULL AFTER id,
    ADD KEY IF NOT EXISTS idx_agency_approvals_agency_id (agency_id);
