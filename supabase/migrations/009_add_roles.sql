-- Add role column to journalists table
ALTER TABLE journalists
ADD COLUMN role ENUM('journalist', 'editor', 'admin') DEFAULT 'journalist';

-- Update existing journalists to have the editor role
UPDATE journalists SET role = 'editor' WHERE id > 0; 