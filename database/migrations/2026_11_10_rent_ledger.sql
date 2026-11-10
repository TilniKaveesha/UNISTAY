-- UniStay manual rent ledger. IMPORTANT: coordinate with the database team.
-- First run SHOW CREATE TABLE `RentPayment` to check for an existing table.
-- If one already exists with different columns, reconcile schema rather than running an incompatible migration.
-- Does not alter or drop User, BoardingListing, Inquiry or Tenancy.

CREATE TABLE IF NOT EXISTS `RentPayment` (
    `payment_id` BIGINT NOT NULL AUTO_INCREMENT,
    `tenancy_id` INT NOT NULL,
    `billing_month` DATE NOT NULL,
    `amount_due` DECIMAL(10,2) NOT NULL,
    `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `due_date` DATE NOT NULL,
    `paid_at` DATETIME DEFAULT NULL,
    `payment_method` VARCHAR(30) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'unpaid',
    `reference_note` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`payment_id`),
    UNIQUE KEY `uniq_rent_tenancy_month` (`tenancy_id`, `billing_month`),
    CONSTRAINT `fk_rent_payment_tenancy`
        FOREIGN KEY (`tenancy_id`) REFERENCES `Tenancy` (`tenancy_id`)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- IMPORTANT: billing_month is always the first day of a calendar month (enforced by PHP).
-- The status field contains only unpaid, partial, or paid. "Overdue" is computed from due_date.
-- A row records owner-entered payment information, NOT a verified transaction.
