ALTER TABLE articles
ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title; 