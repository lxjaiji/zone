CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
);

-- Insert default values so the settings page can run UPDATE queries
-- The user can then change these via the settings page.
INSERT INTO settings (setting_key, setting_value) VALUES
('province_name', 'Province of [Default]'),
('municipality_name', 'Municipality of [Default]'),
('municipality_logo_path', 'uploads/default_logo.png'),
('province_logo_path', 'uploads/default_logo.png');
