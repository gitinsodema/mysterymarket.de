-- MysteryMarket R1.2 Verify credential output/fulfilment service
-- audit_verifications remains the authoritative credential record.

DROP TABLE IF EXISTS credential_orders;
DROP TABLE IF EXISTS credentials;
DROP TABLE IF EXISTS credential_subjects;

CREATE TABLE IF NOT EXISTS verify_credential_outputs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    audit_verification_id BIGINT UNSIGNED NOT NULL,
    requested_by_user_id BIGINT UNSIGNED NULL,
    output_type ENUM(
        'apple_wallet',
        'print_card',
        'physical_card',
        'transparent_holder',
        'mysterymarket_lanyard',
        'elite_shopper_lanyard',
        'full_set',
        'replacement_card'
    ) NOT NULL,
    output_status ENUM('requested','approved','processing','ready','shipped','cancelled') NOT NULL DEFAULT 'requested',
    shipping_reference VARCHAR(120) NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    processed_at DATETIME NULL,
    shipped_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_verify_output_record_status (audit_verification_id, output_status),
    KEY idx_verify_output_type_status (output_type, output_status),
    CONSTRAINT fk_verify_output_record
        FOREIGN KEY (audit_verification_id) REFERENCES audit_verifications(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_verify_output_requested_by
        FOREIGN KEY (requested_by_user_id) REFERENCES backoffice_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
