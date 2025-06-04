# Site d'Actualités

Un système de gestion de contenu (CMS) pour un site d'actualités avec gestion des articles, commentaires et utilisateurs.

## Fonctionnalités

- **Articles**
  - Création et gestion d'articles
  - Catégorisation des articles
  - Support des images et vidéos
  - Système de tags
  - Articles connexes

- **Commentaires**
  - Système de commentaires modérés
  - Interface de modération pour les journalistes
  - Gestion des commentaires par les administrateurs

- **Utilisateurs**
  - Interface d'administration
  - Espace journalistes
  - Gestion des rôles (admin, journaliste, visiteur)

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- XAMPP (recommandé pour le développement)

## Installation

1. Clonez le dépôt dans votre dossier web :
   ```bash
   git clone [URL_DU_REPO] /chemin/vers/votre/dossier/web
   ```

2. Créez une base de données MySQL pour le projet

3. Importez le fichier de base de données :
   ```bash
   mysql -u [utilisateur] -p [nom_de_la_base] < database.sql
   ```

4. Configurez la connexion à la base de données :
   - Copiez `includes/config.example.php` vers `includes/config.php`
   - Modifiez les paramètres de connexion dans `config.php`

5. Assurez-vous que les permissions des dossiers sont correctes :
   ```bash
   chmod 755 -R /chemin/vers/votre/dossier/web
   chmod 777 -R /chemin/vers/votre/dossier/web/uploads
   ```

## Structure des dossiers

```
project/
├── admin/              # Interface d'administration
├── assets/            # Fichiers statiques (CSS, JS, images)
├── includes/          # Fichiers PHP communs
├── journalist/        # Interface des journalistes
├── uploads/           # Dossier pour les fichiers uploadés
└── index.php          # Page d'accueil
```

## Utilisation

### Administration
- Accédez à `/admin` pour l'interface d'administration
- Identifiants par défaut : admin/admin

### Journalistes
- Accédez à `/journalist` pour l'interface des journalistes
- Créez un compte journaliste via l'administration

### Visiteurs
- Accédez à la page d'accueil pour voir les articles
- Commentez les articles (modération activée)

## Sécurité

- Tous les commentaires sont modérés
- Protection contre les injections SQL
- Validation des entrées utilisateur
- Gestion sécurisée des sessions

## Contribution

1. Fork le projet
2. Créez une branche pour votre fonctionnalité
3. Committez vos changements
4. Poussez vers la branche
5. Créez une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## Support

Pour toute question ou problème, veuillez créer une issue dans le dépôt GitHub. 