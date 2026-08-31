-- MysteryMarket R1.2 private credential access binding
-- audit_verifications remains the authoritative credential record.
-- subject_user_id only binds the private holder/login allowed to access representations.

ALTER TABLE audit_verifications
    ADD COLUMN IF NOT EXISTS subject_user_id BIGINT UNSIGNED NULL AFTER is_personal_verification,
    ADD INDEX IF NOT EXISTS idx_audit_subject_user (subject_user_id);

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'audit_verifications'
      AND CONSTRAINT_NAME = 'fk_audit_subject_user'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @fk_sql := IF(
    @fk_exists = 0,
    'ALTER TABLE audit_verifications ADD CONSTRAINT fk_audit_subject_user FOREIGN KEY (subject_user_id) REFERENCES backoffice_users(id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);

PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
