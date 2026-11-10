-- UniStay Cloudinary photo storage (additive; preserves existing records and foreign keys).
-- Coordinate with the database team and apply once in TiDB Cloud SQL Editor.
-- Ensures ListingPhoto exists if the previous gallery migration was not yet applied.

CREATE TABLE IF NOT EXISTS `ListingPhoto` (
    `photo_id` BIGINT NOT NULL AUTO_INCREMENT,
    `listing_id` INT NOT NULL,
    `file_path` VARCHAR(2048) NOT NULL,
    `is_cover` TINYINT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`photo_id`),
    KEY `idx_listing_photo` (`listing_id`, `is_cover`, `photo_id`),
    CONSTRAINT `fk_listing_photo_listing` FOREIGN KEY (`listing_id`)
        REFERENCES `BoardingListing` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

ALTER TABLE `ListingPhoto`
    ADD COLUMN IF NOT EXISTS `cloud_public_id` VARCHAR(255) NULL;

-- Old local paths and manually supplied URLs have cloud_public_id = NULL.
-- Cloudinary images have file_path = secure HTTPS delivery URL, cloud_public_id = cloud asset ID.
-- Review after migration:
-- SHOW COLUMNS FROM `ListingPhoto`;
