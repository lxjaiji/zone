CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE
);

-- Insert an initial admin user (replace 'admin_password' with a hashed password in a real application)
INSERT INTO users (username, password, is_admin) VALUES ('admin', 'admin_password', TRUE);
