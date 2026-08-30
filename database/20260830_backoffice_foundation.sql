-- MysteryMarket R1.1 Little Backoffice foundation
-- MariaDB 12.x / MySQL-compatible DDL

CREATE TABLE IF NOT EXISTS backoffice_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','elite') NOT NULL,
    account_status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_backoffice_users_email (email),
    KEY idx_backoffice_users_role_status (role, account_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS elite_members (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    member_code VARCHAR(32) NOT NULL,
    membership_status ENUM('invited','pending_review','active','paused','suspended','ended') NOT NULL DEFAULT 'invited',
    display_name VARCHAR(150) NOT NULL,
    organisation VARCHAR(200) NULL,
    phone VARCHAR(60) NULL,
    address_line1 VARCHAR(200) NULL,
    address_line2 VARCHAR(200) NULL,
    postal_code VARCHAR(24) NULL,
    city VARCHAR(120) NULL,
    country_code CHAR(2) NULL,
    preferred_regions TEXT NULL,
    mobility_profile VARCHAR(120) NULL,
    shoppermatch_profile_url VARCHAR(500) NULL,
    internal_note TEXT NULL,
    joined_at DATETIME NULL,
    paused_at DATETIME NULL,
    ended_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_elite_members_user_id (user_id),
    UNIQUE KEY uq_elite_members_member_code (member_code),
    KEY idx_elite_members_status (membership_status),
    CONSTRAINT fk_elite_members_user
        FOREIGN KEY (user_id) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS elite_feed_posts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    author_user_id BIGINT UNSIGNED NOT NULL,
    category ENUM('project_hint','agency','training','ops','important','general') NOT NULL DEFAULT 'general',
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    agency_name VARCHAR(200) NULL,
    project_context VARCHAR(255) NULL,
    region_label VARCHAR(160) NULL,
    external_url VARCHAR(500) NULL,
    publish_from DATETIME NULL,
    publish_until DATETIME NULL,
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_elite_feed_visibility (is_active, publish_from, publish_until),
    KEY idx_elite_feed_category (category),
    CONSTRAINT fk_elite_feed_author
        FOREIGN KEY (author_user_id) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agency_approvals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    agency_name VARCHAR(200) NOT NULL,
    contact_name VARCHAR(160) NULL,
    contact_email VARCHAR(254) NULL,
    purpose VARCHAR(255) NOT NULL,
    approval_status ENUM('draft','requested','approved','rejected','expired') NOT NULL DEFAULT 'draft',
    requested_at DATETIME NULL,
    responded_at DATETIME NULL,
    evidence_reference VARCHAR(500) NULL,
    internal_note TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_agency_approvals_status (approval_status),
    KEY idx_agency_approvals_agency (agency_name),
    CONSTRAINT fk_agency_approvals_creator
        FOREIGN KEY (created_by) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backoffice_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id BIGINT UNSIGNED NULL,
    action_key VARCHAR(100) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    metadata_json LONGTEXT NULL,
    ip_hash CHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_backoffice_audit_actor_time (actor_user_id, created_at),
    KEY idx_backoffice_audit_entity (entity_type, entity_id),
    KEY idx_backoffice_audit_time (created_at),
    CONSTRAINT fk_backoffice_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backoffice_login_rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_hash CHAR(64) NOT NULL,
    email_hash CHAR(64) NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_backoffice_login_ip_time (ip_hash, attempted_at),
    KEY idx_backoffice_login_email_time (email_hash, attempted_at),
    KEY idx_backoffice_login_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
