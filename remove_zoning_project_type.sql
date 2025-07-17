-- This script removes the project_type column from the zoning_certificates table.

ALTER TABLE `zoning_certificates`
DROP COLUMN `project_type`;
