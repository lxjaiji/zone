-- Add signatory_name column to zoning_certificates table
ALTER TABLE zoning_certificates
ADD COLUMN signatory_name VARCHAR(255) AFTER or_number;
