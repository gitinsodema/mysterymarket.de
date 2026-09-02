ALTER TABLE contact_requests
    ADD COLUMN IF NOT EXISTS moderation_decision VARCHAR(16) NOT NULL DEFAULT 'undecided' AFTER status,
    ADD COLUMN IF NOT EXISTS moderation_reviewed_at DATETIME NULL AFTER moderation_decision,
    ADD COLUMN IF NOT EXISTS moderation_reviewed_by BIGINT UNSIGNED NULL AFTER moderation_reviewed_at;

SET @mm_contact_moderation_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'contact_requests'
      AND CONSTRAINT_NAME = 'fk_contact_moderation_reviewer'
);

SET @mm_contact_moderation_fk_sql := IF(
    @mm_contact_moderation_fk_exists = 0,
    'ALTER TABLE contact_requests ADD CONSTRAINT fk_contact_moderation_reviewer FOREIGN KEY (moderation_reviewed_by) REFERENCES backoffice_users(id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);

PREPARE mm_contact_moderation_stmt FROM @mm_contact_moderation_fk_sql;
EXECUTE mm_contact_moderation_stmt;
DEALLOCATE PREPARE mm_contact_moderation_stmt;
