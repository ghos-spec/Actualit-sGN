-- Add password column to journalists table
ALTER TABLE journalists
ADD COLUMN password VARCHAR(255) NOT NULL;

-- Update existing journalists with a default password (password: 'password123')
UPDATE journalists 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE password IS NULL; 