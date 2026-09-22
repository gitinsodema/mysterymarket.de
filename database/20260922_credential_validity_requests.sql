-- Controlled validity changes for active Verify credentials

CREATE TABLE IF NOT EXISTS credential_validity_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    credential_id BIGINT UNSIGNED NOT NULL,
    requester_user_id BIGINT UNSIGNED NOT NULL,
    requested_valid_until DATE NOT NULL,
    request_note TEXT NULL,
    request_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_credential_validity_requests_credential (credential_id, request_status, created_at),
    KEY idx_credential_validity_requests_requester (requester_user_id, created_at),
    CONSTRAINT fk_credential_validity_requests_credential
        FOREIGN KEY (credential_id) REFERENCES audit_verifications(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_credential_validity_requests_requester
        FOREIGN KEY (requester_user_id) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_credential_validity_requests_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
