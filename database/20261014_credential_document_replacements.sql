-- Logged admin replacement of credential authorisation document without changing credential identity

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
    KEY idx_credential_document_replacements_project (credential_project_id, created_at),
    CONSTRAINT fk_credential_document_replacements_credential
        FOREIGN KEY (audit_verification_id) REFERENCES audit_verifications(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_credential_document_replacements_project
        FOREIGN KEY (credential_project_id) REFERENCES credential_projects(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_credential_document_replacements_user
        FOREIGN KEY (replaced_by) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
