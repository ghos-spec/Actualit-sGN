-- Supprimer la colonne role si elle existe déjà
ALTER TABLE admin_users DROP COLUMN IF EXISTS role;

-- Ajouter la colonne role avec les contraintes appropriées
ALTER TABLE admin_users 
ADD COLUMN role ENUM('Superadmin', 'Admin', 'Editor') NOT NULL DEFAULT 'Editor' AFTER email;

-- Mettre à jour l'utilisateur admin existant en Superadmin
UPDATE admin_users 
SET role = 'Superadmin' 
WHERE username = 'admin';

-- Ajouter un index sur la colonne role pour optimiser les recherches
CREATE INDEX idx_admin_users_role ON admin_users(role); 