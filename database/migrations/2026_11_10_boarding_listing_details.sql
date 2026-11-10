-- UniStay · richer boarding listing details
-- Apply in TiDB Cloud SQL Editor BEFORE deploying the upgraded PHP pages.
-- Additive only: does not change any IDs or foreign-key relationships.
-- Existing listings may have NULL detail fields; the new UI displays safe fallbacks.
-- Each statement may be rerun safely if the column already exists.

ALTER TABLE `BoardingListing` ADD COLUMN IF NOT EXISTS `title` VARCHAR(160) NULL;
ALTER TABLE `BoardingListing` ADD COLUMN IF NOT EXISTS `address` VARCHAR(300) NULL;
ALTER TABLE `BoardingListing` ADD COLUMN IF NOT EXISTS `description` TEXT NULL;
ALTER TABLE `BoardingListing` ADD COLUMN IF NOT EXISTS `facilities` TEXT NULL;
ALTER TABLE `BoardingListing` ADD COLUMN IF NOT EXISTS `gender_category` VARCHAR(20) NOT NULL DEFAULT 'any';
ALTER TABLE `BoardingListing` ADD COLUMN IF NOT EXISTS `available_from` DATE NULL;
ALTER TABLE `BoardingListing` ADD COLUMN IF NOT EXISTS `cover_image_url` VARCHAR(2048) NULL;

-- Verify after migration:
-- SHOW COLUMNS FROM `BoardingListing`;
