-- This script adds new fields for Lot Number and Land Area to the zoning certificates.

ALTER TABLE `zoning_certificates`
ADD COLUMN `lot_no` VARCHAR(100) NULL AFTER `tax_declaration`,
ADD COLUMN `land_area` VARCHAR(100) NULL AFTER `lot_no`;
