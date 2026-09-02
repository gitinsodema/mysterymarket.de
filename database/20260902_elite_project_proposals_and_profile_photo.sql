-- Elite profile photo + controlled project proposal workflow

ALTER TABLE elite_members
    ADD COLUMN IF NOT EXISTS profile_photo_asset VARCHAR(255) NULL AFTER display_name;

ALTER TABLE credential_projects
    ADD COLUMN IF NOT EXISTS project_logo_asset VARCHAR(255) NULL AFTER project_name,
    ADD COLUMN IF NOT EXISTS authorization_document_asset VARCHAR(255) NULL AFTER project_logo_asset,
    ADD COLUMN IF NOT EXISTS authorization_document_label VARCHAR(200) NULL AFTER authorization_document_asset;

CREATE TABLE IF NOT EXISTS credential_project_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id BIGINT UNSIGNED NOT NULL,
    agency_id BIGINT UNSIGNED NOT NULL,
    project_name VARCHAR(200) NOT NULL,
    authorization_document_asset VARCHAR(255) NOT NULL,
    request_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_note TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    approved_project_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_credential_project_requests_status (request_status, created_at),
    KEY idx_credential_project_requests_member (member_id, created_at),
    KEY idx_credential_project_requests_agency (agency_id),
    CONSTRAINT fk_credential_project_requests_member
        FOREIGN KEY (member_id) REFERENCES elite_members(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_credential_project_requests_agency
        FOREIGN KEY (agency_id) REFERENCES agencies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_credential_project_requests_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_credential_project_requests_project
        FOREIGN KEY (approved_project_id) REFERENCES credential_projects(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO credential_roles (label, sort_order)
VALUES ('Testkäufer', 25);

UPDATE credential_projects
SET customer_name = project_name
WHERE customer_name <> project_name;

UPDATE audit_verifications v
JOIN credential_projects p ON p.id = v.credential_project_id
SET v.brand_name = p.project_name,
    v.public_client = p.project_name
WHERE v.is_personal_verification = 1;
