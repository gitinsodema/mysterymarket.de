-- MysteryMarket R1.2 credential subject boundary
-- Credentials belong to a subject identity; backoffice role is only an access role.

CREATE TABLE IF NOT EXISTS credential_subjects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    backoffice_user_id BIGINT UNSIGNED NULL,
    display_name VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_credential_subject_user (backoffice_user_id),
    CONSTRAINT fk_credential_subject_user
        FOREIGN KEY (backoffice_user_id) REFERENCES backoffice_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO credential_subjects (backoffice_user_id, display_name)
SELECT m.user_id, m.display_name
FROM elite_members m
LEFT JOIN credential_subjects s ON s.backoffice_user_id = m.user_id
WHERE s.id IS NULL;

ALTER TABLE audit_verifications
    ADD COLUMN IF NOT EXISTS credential_subject_id BIGINT UNSIGNED NULL AFTER is_personal_verification,
    ADD KEY IF NOT EXISTS idx_audit_credential_subject (credential_subject_id);

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'audit_verifications'
      AND CONSTRAINT_NAME = 'fk_audit_credential_subject'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @fk_sql := IF(
    @fk_exists = 0,
    'ALTER TABLE audit_verifications ADD CONSTRAINT fk_audit_credential_subject FOREIGN KEY (credential_subject_id) REFERENCES credential_subjects(id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
