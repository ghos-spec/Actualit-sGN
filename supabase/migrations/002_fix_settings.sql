-- Drop settings table if exists
DROP TABLE IF EXISTS settings;

-- Recreate settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (`key`, value) VALUES
('site_title', 'Actualités Gabonaises'),
('site_description', 'Votre source d''actualités au Gabon'); 