-- Insert default admin account
-- Password is 'admin123' (hashed with password_hash)
INSERT INTO journalists (name, email, password, title, bio) VALUES
('Administrateur', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'Compte administrateur principal'); 