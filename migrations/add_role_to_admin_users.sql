-- Ajouter la colonne role à la table admin_users
ALTER TABLE admin_users
ADD COLUMN role VARCHAR(50) DEFAULT 'Journaliste' AFTER email;

-- Mettre à jour l'utilisateur admin existant en Superadmin
UPDATE admin_users 
SET role = 'Superadmin' 
WHERE username = 'admin'; 