-- This script removes the unnecessary signatory_name column from the transaction tables.
-- The definitive signatory name should only be stored in the `settings` table.

ALTER TABLE `locational_clearances`
DROP COLUMN `signatory_name`;

ALTER TABLE `zoning_certificates`
DROP COLUMN `signatory_name`;
