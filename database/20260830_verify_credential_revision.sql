-- MysteryMarket R1.2 Verify credential revision lineage
ALTER TABLE audit_verifications
    ADD COLUMN IF NOT EXISTS supersedes_verification_id BIGINT UNSIGNED NULL AFTER is_active,
    ADD COLUMN IF NOT EXISTS revision_no INT UNSIGNED NOT NULL DEFAULT 1 AFTER supersedes_verification_id;
