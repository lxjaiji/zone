-- This script adds the missing amount_paid column to the fishing_gear_permits table.

ALTER TABLE `fishing_gear_permits`
ADD COLUMN `amount_paid` DECIMAL(10, 2) NULL DEFAULT NULL AFTER `or_number`;
