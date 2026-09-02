-- Controlled credential master data + photo permission

CREATE TABLE IF NOT EXISTS credential_roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    label VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_credential_roles_label (label),
    KEY idx_credential_roles_active_sort (is_active, sort_order, label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS credential_projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    agency_id BIGINT UNSIGNED NOT NULL,
    customer_name VARCHAR(200) NOT NULL,
    project_name VARCHAR(200) NOT NULL,
    scope_key VARCHAR(80) NULL,
    photo_allowed TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_credential_project_identity (agency_id, customer_name, project_name),
    KEY idx_credential_projects_active (is_active, project_name),
    KEY idx_credential_projects_agency (agency_id),
    CONSTRAINT fk_credential_projects_agency
        FOREIGN KEY (agency_id) REFERENCES agencies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE audit_verifications
    ADD COLUMN IF NOT EXISTS credential_role_id BIGINT UNSIGNED NULL AFTER role_label,
    ADD COLUMN IF NOT EXISTS agency_id BIGINT UNSIGNED NULL AFTER agency_name,
    ADD COLUMN IF NOT EXISTS credential_project_id BIGINT UNSIGNED NULL AFTER project_name,
    ADD COLUMN IF NOT EXISTS photo_allowed TINYINT(1) NOT NULL DEFAULT 0 AFTER print_card_enabled;

INSERT IGNORE INTO credential_roles (label, sort_order)
VALUES
    ('Independent Field Auditor', 10),
    ('Field Auditor', 20),
    ('Mystery Shopper', 30),
    ('Auditor', 40);

INSERT IGNORE INTO credential_roles (label, sort_order)
SELECT DISTINCT TRIM(role_label), 90
FROM audit_verifications
WHERE is_personal_verification = 1
  AND TRIM(COALESCE(role_label, '')) <> '';

INSERT IGNORE INTO agencies (name, short_name, is_active)
SELECT DISTINCT TRIM(agency_name), TRIM(agency_name), 1
FROM audit_verifications
WHERE is_personal_verification = 1
  AND TRIM(COALESCE(agency_name, '')) <> '';

INSERT IGNORE INTO credential_projects (agency_id, customer_name, project_name, scope_key, photo_allowed, is_active)
SELECT a.id,
       TRIM(v.brand_name),
       TRIM(v.project_name),
       NULLIF(TRIM(v.scope_key), ''),
       COALESCE(v.photo_allowed, 0),
       1
FROM audit_verifications v
JOIN agencies a ON a.name = TRIM(v.agency_name)
WHERE v.is_personal_verification = 1
  AND TRIM(COALESCE(v.brand_name, '')) <> ''
  AND TRIM(COALESCE(v.project_name, '')) <> '';

UPDATE audit_verifications v
JOIN credential_roles r ON r.label = TRIM(v.role_label)
SET v.credential_role_id = r.id
WHERE v.is_personal_verification = 1
  AND v.credential_role_id IS NULL;

UPDATE audit_verifications v
JOIN agencies a ON a.name = TRIM(v.agency_name)
SET v.agency_id = a.id
WHERE v.is_personal_verification = 1
  AND v.agency_id IS NULL;

UPDATE audit_verifications v
JOIN credential_projects p
  ON p.agency_id = v.agency_id
 AND p.customer_name = TRIM(v.brand_name)
 AND p.project_name = TRIM(v.project_name)
SET v.credential_project_id = p.id
WHERE v.is_personal_verification = 1
  AND v.credential_project_id IS NULL;
