-- Ajouter les colonnes de notification à la table admin_users
ALTER TABLE admin_users
ADD COLUMN notifications TINYINT(1) DEFAULT 1,
ADD COLUMN email_notifications TINYINT(1) DEFAULT 1;

-- Créer la table login_history
CREATE TABLE IF NOT EXISTS login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 