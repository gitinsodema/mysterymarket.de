CREATE TABLE IF NOT EXISTS contact_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_code CHAR(5) NULL,
    request_type VARCHAR(32) NOT NULL,
    name VARCHAR(150) NOT NULL,
    organisation VARCHAR(200) NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(60) NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    privacy_acknowledged_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'new',
    moderation_decision VARCHAR(16) NOT NULL DEFAULT 'undecided',
    moderation_reviewed_at DATETIME NULL,
    moderation_reviewed_by BIGINT UNSIGNED NULL,
    notification_sent_at DATETIME NULL,
    notification_failed_at DATETIME NULL,
    confirmation_sent_at DATETIME NULL,
    confirmation_failed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contact_reference_code (reference_code),
    KEY idx_contact_created_at (created_at),
    KEY idx_contact_status (status),
    KEY idx_contact_moderation_decision (moderation_decision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_verifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_code VARCHAR(64) NOT NULL,
    public_title VARCHAR(200) NOT NULL,
    public_partner VARCHAR(200) NULL,
    public_client VARCHAR(200) NULL,
    valid_from DATE NULL,
    valid_until DATE NULL,
    confidentiality_mode ENUM('public','confidential') NOT NULL DEFAULT 'public',
    public_note TEXT NULL,
    person_name VARCHAR(150) NULL,
    role_label VARCHAR(120) NULL,
    credential_role_id BIGINT UNSIGNED NULL,
    agency_name VARCHAR(200) NULL,
    agency_id BIGINT UNSIGNED NULL,
    project_name VARCHAR(200) NULL,
    credential_project_id BIGINT UNSIGNED NULL,
    brand_name VARCHAR(200) NULL,
    photo_asset VARCHAR(255) NULL,
    brand_logo_asset VARCHAR(255) NULL,
    agency_logo_asset VARCHAR(255) NULL,
    scope_key VARCHAR(80) NULL,
    document_asset VARCHAR(255) NULL,
    document_label VARCHAR(200) NULL,
    document_enabled TINYINT(1) NOT NULL DEFAULT 0,
    print_card_enabled TINYINT(1) NOT NULL DEFAULT 0,
    photo_allowed TINYINT(1) NOT NULL DEFAULT 0,
    is_personal_verification TINYINT(1) NOT NULL DEFAULT 0,
    subject_user_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_audit_reference_code (reference_code),
    KEY idx_audit_active (is_active),
    KEY idx_audit_valid_until (valid_until),
    KEY idx_audit_subject_user (subject_user_id),
    KEY idx_audit_credential_role (credential_role_id),
    KEY idx_audit_agency_id (agency_id),
    KEY idx_audit_credential_project (credential_project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS verify_rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_hash CHAR(64) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_verify_rate_ip_time (ip_hash, attempted_at),
    KEY idx_verify_rate_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS contact_rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_hash CHAR(64) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_rate_ip_time (ip_hash, attempted_at),
    KEY idx_contact_rate_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS credential_document_replacements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    audit_verification_id BIGINT UNSIGNED NOT NULL,
    credential_project_id BIGINT UNSIGNED NULL,
    previous_document_asset VARCHAR(255) NULL,
    new_document_asset VARCHAR(255) NOT NULL,
    previous_valid_until DATE NULL,
    new_valid_until DATE NOT NULL,
    replaced_by BIGINT UNSIGNED NOT NULL,
    note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_credential_document_replacements_credential (audit_verification_id, created_at),
    KEY idx_credential_document_replacements_project (credential_project_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
