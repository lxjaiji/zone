CREATE TABLE locational_clearances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_name VARCHAR(255) NOT NULL,
    owner_name VARCHAR(255) NOT NULL, -- Assuming owner name is still relevant
    address TEXT NOT NULL,
    date_filed DATE NOT NULL,
    issue_date DATE NOT NULL,
    clearance_number VARCHAR(50) NOT NULL UNIQUE,
    expiration_date DATE NOT NULL,
    tax_declaration VARCHAR(100),
    project_type VARCHAR(255) NOT NULL,
    project_location TEXT NOT NULL,
    purpose TEXT,
    -- Specific to Locational Clearance, adjust as needed. Using a general 'land_use_classification' for now.
    land_use_classification VARCHAR(255),
    fees_paid DECIMAL(10, 2) NOT NULL,
    or_number VARCHAR(100) NOT NULL,

    -- Clickable Conditions (BOOLEAN/TINYINT(1) to store true/false)
    condition1_monitoring BOOLEAN DEFAULT FALSE,
    condition2_non_compliance BOOLEAN DEFAULT FALSE,
    condition3_other_agencies BOOLEAN DEFAULT FALSE,
    condition4_activity_applied_for BOOLEAN DEFAULT FALSE,
    condition5_no_major_expansion BOOLEAN DEFAULT FALSE,
    condition6_not_cert_ownership BOOLEAN DEFAULT FALSE,
    condition7_misrepresentation BOOLEAN DEFAULT FALSE,
    condition8_commencement_period BOOLEAN DEFAULT FALSE,

    -- Signatory Information
    signatory_name VARCHAR(255), -- Name of MPDC/Zoning Administrator

    encoded_by_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (encoded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Sample data (optional, for testing)
/*
INSERT INTO locational_clearances (
    applicant_name, owner_name, address, date_filed, issue_date, clearance_number, expiration_date,
    tax_declaration, project_type, project_location, purpose, land_use_classification,
    fees_paid, or_number,
    condition1_monitoring, condition2_non_compliance, condition3_other_agencies, condition4_activity_applied_for,
    condition5_no_major_expansion, condition6_not_cert_ownership, condition7_misrepresentation, condition8_commencement_period,
    signatory_name, encoded_by_user_id
) VALUES (
    'Alice Wonderland Applicant', 'Mad Hatter Owner', '45 Tea Party Lane, Wonderland', '2024-03-01', '2024-03-01', 'LC-2024-001', '2025-03-01',
    'TDN-W001', 'Commercial Tea Shop', 'Next to the March Hare House', 'To serve tea and cakes', 'Commercial Zone',
    2500.00, 'ORLC654321',
    TRUE, TRUE, TRUE, TRUE,
    TRUE, TRUE, TRUE, FALSE,
    'Mr. White Rabbit (MPDC)', 1
);
*/
