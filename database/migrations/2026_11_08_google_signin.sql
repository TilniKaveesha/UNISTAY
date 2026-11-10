ALTER TABLE users ADD COLUMN google_sub VARCHAR(255) NULL;
CREATE UNIQUE INDEX uq_users_google_sub ON users (google_sub);
-- UniStay TiDB migration for the EXISTING `User` table.
-- Do not drop or rename `User`: BoardingListing, Inquiry and Tenancy reference it.
-- Before running, the database team should confirm there are still zero users
-- and no conflicting indexes/sequence already created.

-- Account suspension and admin-user ordering.
ALTER TABLE `User`
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'active';
ALTER TABLE `User`
    ADD COLUMN IF NOT EXISTS `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Unique account identities. Nullable google_sub supports password-only users.
-- Run these CREATE INDEX statements once. Existing duplicate values would
-- prevent index creation, so inspect data first if any users were added.
CREATE UNIQUE INDEX `uq_user_email` ON `User` (`email`);
CREATE UNIQUE INDEX `uq_user_google_sub` ON `User` (`google_sub`);

-- Existing user_id is INT PRIMARY KEY, not AUTO_INCREMENT; TiDB cannot add
-- AUTO_INCREMENT through ALTER TABLE on an existing column.
-- All new account-registration code must obtain IDs from this sequence.
CREATE SEQUENCE IF NOT EXISTS `unistay_user_id_seq`
    START WITH 1
    MINVALUE 1
    MAXVALUE 2147483647
    INCREMENT BY 1
    NOCYCLE;
