-- MysteryMarket R1.2 credential identity and issuance foundation
-- Credentials belong to a person/identity, not to a login role.

CREATE TABLE IF NOT EXISTS credential_subjects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    backoffice_user_id BIGINT UNSIGNED NULL,
    display_name VARCHAR(150) NOT NULL,
    subject_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_credential_subject_user (backoffice_user_id),
    CONSTRAINT fk_credential_subject_user
        FOREIGN KEY (backoffice_user_id) REFERENCES backoffice_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS credentials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_id BIGINT UNSIGNED NOT NULL,
    credential_code VARCHAR(40) NOT NULL,
    credential_type ENUM('elite_shopper','auditor','field_credential') NOT NULL DEFAULT 'elite_shopper',
    title VARCHAR(160) NOT NULL,
    credential_status ENUM('draft','approved','active','expired','revoked','replaced') NOT NULL DEFAULT 'draft',
    valid_from DATE NULL,
    valid_until DATE NULL,
    verify_reference_code VARCHAR(64) NULL,
    replaces_credential_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    issued_at DATETIME NULL,
    revoked_at DATETIME NULL,
    replaced_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_credentials_code (credential_code),
    UNIQUE KEY uq_credentials_verify_reference (verify_reference_code),
    KEY idx_credentials_subject_status (subject_id, credential_status),
    KEY idx_credentials_valid_until (valid_until),
    CONSTRAINT fk_credentials_subject
        FOREIGN KEY (subject_id) REFERENCES credential_subjects(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_credentials_replaces
        FOREIGN KEY (replaces_credential_id) REFERENCES credentials(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_credentials_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES backoffice_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS credential_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    credential_id BIGINT UNSIGNED NOT NULL,
    requested_by_user_id BIGINT UNSIGNED NULL,
    order_channel ENUM(
        'apple_wallet',
        'physical_card',
        'transparent_holder',
        'mysterymarket_lanyard',
        'elite_shopper_lanyard',
        'full_set',
        'replacement_card'
    ) NOT NULL,
    order_status ENUM('requested','approved','processing','ready','shipped','cancelled') NOT NULL DEFAULT 'requested',
    shipping_reference VARCHAR(120) NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    processed_at DATETIME NULL,
    shipped_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_credential_orders_credential_status (credential_id, order_status),
    KEY idx_credential_orders_channel_status (order_channel, order_status),
    CONSTRAINT fk_credential_orders_credential
        FOREIGN KEY (credential_id) REFERENCES credentials(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_credential_orders_requested_by
        FOREIGN KEY (requested_by_user_id) REFERENCES backoffice_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO credential_subjects (backoffice_user_id, display_name, subject_status)
SELECT u.id, m.display_name, 'active'
FROM backoffice_users u
JOIN elite_members m ON m.user_id = u.id
LEFT JOIN credential_subjects s ON s.backoffice_user_id = u.id
WHERE s.id IS NULL;
