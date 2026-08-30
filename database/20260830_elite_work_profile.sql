-- MysteryMarket R1.1 Elite work-profile refinement

ALTER TABLE elite_members
    ADD COLUMN IF NOT EXISTS work_profile VARCHAR(40) NULL AFTER organisation;
