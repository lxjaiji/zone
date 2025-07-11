CREATE TABLE zoning_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_name VARCHAR(255) NOT NULL,
    owner_name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    date_filed DATE NOT NULL,
    issue_date DATE NOT NULL,
    certificate_number VARCHAR(50) NOT NULL UNIQUE,
    expiration_date DATE NOT NULL,
    tax_declaration VARCHAR(100),
    project_type VARCHAR(255) NOT NULL,
    project_location TEXT NOT NULL,
    purpose TEXT,
    zoning_classification ENUM('Residential', 'Commercial', 'Agro-Industrial', 'Agricultural', 'Institutional') NOT NULL,
    fees_paid DECIMAL(10, 2) NOT NULL,
    or_number VARCHAR(100) NOT NULL,
    encoded_by_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (encoded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Sample data (optional, for testing)
/*
INSERT INTO zoning_certificates (
    applicant_name, owner_name, address, date_filed, issue_date, certificate_number, expiration_date,
    tax_declaration, project_type, project_location, purpose, zoning_classification,
    fees_paid, or_number, encoded_by_user_id
) VALUES (
    'John Doe Applicant', 'Jane Doe Owner', '123 Main St, Anytown', '2024-01-15', '2024-01-15', 'ZC-2024-001', '2025-01-15',
    'TDN-00123', 'Residential Building Construction', 'Lot 456, Block 7, Anytown Subd.', 'For Residential Use', 'Residential',
    1500.00, 'OR123456', 1
);
*/
