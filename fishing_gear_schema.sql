CREATE TABLE fishing_gear_permits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gear_type ENUM('Taba', 'Bentahan', 'Fish Cage', 'Talabahan') NOT NULL,
    owner_name VARCHAR(255) NOT NULL,
    owner_resident_of VARCHAR(255),
    location VARCHAR(255) NOT NULL,
    issue_date DATE NOT NULL,
    or_number VARCHAR(100),
    amount_paid DECIMAL(10, 2),
    date_paid DATE,
    issued_at VARCHAR(255),
    encoded_by_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (encoded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
