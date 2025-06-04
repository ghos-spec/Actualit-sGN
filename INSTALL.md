# Guide d'Installation du Site d'Actualités

Ce guide vous aidera à installer et configurer le site d'actualités sur votre machine locale.

## 1. Préparation de l'environnement

### Installation de XAMPP
1. Téléchargez XAMPP depuis [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Installez XAMPP en suivant l'assistant d'installation
3. Démarrez les services Apache et MySQL depuis le panneau de contrôle XAMPP

### Vérification des prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Apache 2.4 ou supérieur

## 2. Installation du projet

### Cloner le projet
1. Ouvrez un terminal dans le dossier `C:\xampp\htdocs`
2. Exécutez la commande :
   ```bash
   git clone https://github.com/ghos-spec project
   ```

### Configuration de la base de données
1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
2. Créez une nouvelle base de données nommée `news_db`
3. Importez le fichier `database.sql` :
   - Cliquez sur la base de données `news_db`
   - Allez dans l'onglet "Importer"
   - Sélectionnez le fichier `database.sql`
   - Cliquez sur "Exécuter"

### Configuration du projet
1. Copiez le fichier de configuration :
   ```bash
   copy includes\config.example.php includes\config.php
   ```

2. Modifiez le fichier `includes/config.php` avec vos paramètres :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'news_db');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Mot de passe par défaut vide pour XAMPP
   ```

### Configuration des permissions
1. Assurez-vous que le dossier `uploads` est accessible en écriture :
   - Clic droit sur le dossier `uploads`
   - Propriétés → Sécurité
   - Ajoutez les permissions d'écriture pour l'utilisateur du serveur web

## 3. Vérification de l'installation

### Test de l'accès
1. Ouvrez votre navigateur
2. Accédez à http://localhost/project
3. Vous devriez voir la page d'accueil du site

### Test de l'administration
1. Accédez à http://localhost/project/admin
2. Connectez-vous avec les identifiants par défaut :
   - Utilisateur : admin
   - Mot de passe : admin123

### Test du journaliste
1. Accédez à http://localhost/project/journalist
2. Accedez à la page d'accueil grace à l'identifiant et le mot de passe donner lors de l'inscription depuis l'administration
3. Connectez-vous avec les identifiants :
   - Identifiant : [votre email]
   - Mot de passe : [votre mot de passe]
NB: Si vous n'avez pas de compte journaliste, vous pouvez créer un compte en vous connectant à l'administration. Le mot de generer automatiquement lors de l'inscription (verifier votre base de données pour le mot de passe).

### Test des fonctionnalités
1. Créez un article test
2. Testez l'upload d'images
3. Testez le système de commentaires

## 4. Dépannage courant

### Problèmes de connexion à la base de données
- Vérifiez que MySQL est en cours d'exécution
- Vérifiez les paramètres dans `config.php`
- Vérifiez que la base de données `news_db` existe

### Problèmes d'upload de fichiers
- Vérifiez les permissions du dossier `uploads`
- Vérifiez la configuration PHP pour `upload_max_filesize` et `post_max_size`

### Problèmes d'affichage
- Vérifiez que tous les fichiers sont bien copiés
- Vérifiez les logs d'erreur Apache dans `C:\xampp\apache\logs`

## 5. Configuration supplémentaire

### Configuration du serveur mail (optionnel)
Pour activer l'envoi d'emails (notifications, réinitialisation de mot de passe) :
1. Modifiez le fichier `php.ini`
2. Configurez les paramètres SMTP :
   ```ini
   [mail function]
   SMTP = localhost
   smtp_port = 25
   sendmail_from = votre@email.com
   ```

### Configuration de la sécurité
1. Changez les mots de passe par défaut
2. Configurez HTTPS si nécessaire
3. Mettez à jour les permissions des fichiers sensibles

## 6. Mise à jour

Pour mettre à jour le projet :
1. Sauvegardez votre base de données
2. Sauvegardez vos fichiers de configuration
3. Exécutez :
   ```bash
   git pull origin main
   ```
4. Importez les mises à jour de la base de données si nécessaire

## Support

Si vous rencontrez des problèmes :
1. Consultez la documentation
2. Vérifiez les logs d'erreur
3. Créez une issue sur GitHub
4. Contactez l'équipe de support 