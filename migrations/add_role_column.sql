-- Vérifier si la colonne role n'existe pas déjà
SET @dbname = DATABASE();
SET @tablename = "admin_users";
SET @columnname = "role";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 'Column role already exists'",
  "ALTER TABLE admin_users ADD COLUMN role VARCHAR(50) DEFAULT 'Journaliste' AFTER email"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Mettre à jour l'utilisateur admin existant en Superadmin
UPDATE admin_users 
SET role = 'Superadmin' 
WHERE username = 'admin'; 