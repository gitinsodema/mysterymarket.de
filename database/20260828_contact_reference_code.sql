ALTER TABLE contact_requests
    ADD COLUMN IF NOT EXISTS reference_code CHAR(5) NULL AFTER id;

SET @index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'contact_requests'
      AND index_name = 'uq_contact_reference_code'
);

SET @sql := IF(
    @index_exists = 0,
    'ALTER TABLE contact_requests ADD UNIQUE KEY uq_contact_reference_code (reference_code)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
