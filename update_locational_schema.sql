-- This script redesigns the locational_clearances table.
-- NOTE: Run this only once. Running it multiple times will result in errors.

-- Rename owner_name to developer_name
ALTER TABLE `locational_clearances` CHANGE COLUMN `owner_name` `developer_name` VARCHAR(255) NOT NULL;

-- Rename project_type to project_name
ALTER TABLE `locational_clearances` CHANGE COLUMN `project_type` `project_name` VARCHAR(255) NOT NULL;

-- Drop the old tax_declaration column
ALTER TABLE `locational_clearances` DROP COLUMN `tax_declaration`;

-- Add new columns
ALTER TABLE `locational_clearances`
ADD COLUMN `developer_address` TEXT NULL AFTER `developer_name`,
ADD COLUMN `right_over_land` VARCHAR(255) NULL AFTER `project_name`,
ADD COLUMN `land_area` VARCHAR(100) NULL AFTER `right_over_land`,
ADD COLUMN `building_area` VARCHAR(100) NULL AFTER `land_area`,
ADD COLUMN `decision` TEXT NULL AFTER `building_area`,
ADD COLUMN `condition9_revoked` BOOLEAN NOT NULL DEFAULT FALSE AFTER `condition8_commencement_period`,
ADD COLUMN `condition10_provisional` BOOLEAN NOT NULL DEFAULT FALSE AFTER `condition9_revoked`;
