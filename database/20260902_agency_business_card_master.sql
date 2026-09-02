-- Agency business-card master data + authoritative agency logo

ALTER TABLE agencies
    ADD COLUMN IF NOT EXISTS logo_asset VARCHAR(255) NULL AFTER website_url,
    ADD COLUMN IF NOT EXISTS logo_source_url VARCHAR(1000) NULL AFTER logo_asset,
    ADD COLUMN IF NOT EXISTS address_line1 VARCHAR(200) NULL AFTER logo_source_url,
    ADD COLUMN IF NOT EXISTS address_line2 VARCHAR(200) NULL AFTER address_line1,
    ADD COLUMN IF NOT EXISTS postal_code VARCHAR(24) NULL AFTER address_line2,
    ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL AFTER postal_code,
    ADD COLUMN IF NOT EXISTS country_code CHAR(2) NULL AFTER city,
    ADD COLUMN IF NOT EXISTS contact_name VARCHAR(160) NULL AFTER country_code,
    ADD COLUMN IF NOT EXISTS contact_email VARCHAR(254) NULL AFTER contact_name,
    ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(60) NULL AFTER contact_email,
    ADD COLUMN IF NOT EXISTS public_note TEXT NULL AFTER contact_phone,
    ADD COLUMN IF NOT EXISTS elite_visible TINYINT(1) NOT NULL DEFAULT 0 AFTER public_note;

UPDATE agencies a
JOIN (
    SELECT v.agency_id, MAX(v.id) AS verification_id
    FROM audit_verifications v
    WHERE v.is_personal_verification = 1
      AND v.agency_id IS NOT NULL
      AND TRIM(COALESCE(v.agency_logo_asset, '')) <> ''
    GROUP BY v.agency_id
) latest ON latest.agency_id = a.id
JOIN audit_verifications source_v ON source_v.id = latest.verification_id
SET a.logo_asset = source_v.agency_logo_asset
WHERE a.logo_asset IS NULL;
