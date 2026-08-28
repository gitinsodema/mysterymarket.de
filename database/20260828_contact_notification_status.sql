ALTER TABLE contact_requests
    ADD COLUMN IF NOT EXISTS notification_sent_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS notification_failed_at DATETIME NULL AFTER notification_sent_at;
