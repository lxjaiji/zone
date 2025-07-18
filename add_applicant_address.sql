-- This script adds the applicant_address column to the locational_clearances table.

ALTER TABLE `locational_clearances`
ADD COLUMN `applicant_address` TEXT NULL AFTER `applicant_name`;
