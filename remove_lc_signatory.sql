-- This script removes the unnecessary signatory_name column from the locational_clearances table.
-- The definitive signatory name should only be stored in the `settings` table.

ALTER TABLE `locational_clearances`
DROP COLUMN `signatory_name`;
