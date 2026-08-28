ALTER TABLE contact_requests
    ADD COLUMN IF NOT EXISTS notification_sent_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS notification_failed_at DATETIME NULL AFTER notification_sent_at,
    ADD COLUMN IF NOT EXISTS confirmation_sent_at DATETIME NULL AFTER notification_failed_at,
    ADD COLUMN IF NOT EXISTS confirmation_failed_at DATETIME NULL AFTER confirmation_sent_at;
