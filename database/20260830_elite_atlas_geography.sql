-- MysteryMarket R1.1 ATLAS geography references for Elite profiles
-- External ATLAS IDs intentionally have no database foreign keys.

ALTER TABLE elite_members
    ADD COLUMN IF NOT EXISTS administrative_unit_atlas_id VARCHAR(191) NULL AFTER country_code,
    ADD COLUMN IF NOT EXISTS postal_area_atlas_id VARCHAR(191) NULL AFTER administrative_unit_atlas_id,
    ADD COLUMN IF NOT EXISTS locality_atlas_id VARCHAR(191) NULL AFTER postal_code,
    ADD COLUMN IF NOT EXISTS locality_name VARCHAR(160) NULL AFTER locality_atlas_id,
    ADD COLUMN IF NOT EXISTS street_atlas_id VARCHAR(191) NULL AFTER locality_name,
    ADD COLUMN IF NOT EXISTS street_name VARCHAR(200) NULL AFTER street_atlas_id,
    ADD COLUMN IF NOT EXISTS house_number VARCHAR(40) NULL AFTER street_name;
