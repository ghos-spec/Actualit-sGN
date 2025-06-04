<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Espace Journaliste' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles personnalisés pour l'espace journaliste (si vous en avez) -->
    <!-- <link href="../assets/css/journalist_styles.css" rel="stylesheet"> -->

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Source Sans Pro', sans-serif;
        }
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
        }
        .content-wrapper {
            margin-top: 20px; /* Ajustez selon la hauteur de votre navbar */
        }
         /* Styles pour les messages flash */
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 250px;
        }
    </style>

</head>
<body>
    <!-- Navbar Journaliste -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Actualités<span class="text-warning">GN</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#journalistNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="journalistNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="articles.php">Mes articles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_article.php">Nouvel article</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Mon Profil</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['journalist_name'])): ?>
                        <span class="navbar-text me-3">
                            Connecté en tant que <?= htmlspecialchars($_SESSION['journalist_name']) ?>
                        </span>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteneur principal du contenu (souvent utilisé avec AdminLTE) -->
    <!-- Le contenu spécifique de chaque page ira ici -->

    <!-- Messages flash -->
    <?php 
    // Afficher les messages flash s'ils existent
    if (isset($_SESSION['flash_message'])) {
        $flash_message = $_SESSION['flash_message'];
        $flash_type = $_SESSION['flash_type'] ?? 'success'; // Type par défaut : succès
        // Nettoyer les variables de session pour ne pas réafficher le message
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    ?>
        <div class="toast show flash-message bg-<?= $flash_type ?> text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($flash_message) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php 
    }
    ?>
</body>
</html> 