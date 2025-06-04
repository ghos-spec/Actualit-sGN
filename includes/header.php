<?php
// Obtenir toutes les catégories pour le menu de navigation
$categories = getAllCategories($conn);

// Obtenir les derniers articles pour le fil d'actualités
$latestArticles = getLatestArticles($conn, 5);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'Actualités Gabonaises' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="top-bar-left">
                        <div class="date-time">
                            <i class="bi bi-calendar3"></i> <?= date(DATE_FORMAT) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="top-bar-right">
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="social-link"><i class="bi bi-twitter"></i></a>
                            <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="social-link"><i class="bi bi-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="index.php">
                        <h1 class="site-title">Actualités<span>GN</span></h1>
                    </a>
                </div>
                <div class="header-search">
                    <form action="search.php" method="get">
                        <div class="input-group">
                            <input type="text" class="form-control" name="q" placeholder="Rechercher...">
                            <button class="btn btn-search" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="container">
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation">
                <i class="bi bi-list"></i> Menu
            </button>
            <div class="collapse navbar-collapse d-md-block" id="mainNavigation">
                <ul class="main-menu">
                    <li class="menu-item"><a href="index.php">Accueil</a></li>
                    <?php foreach ($categories as $navCategory): ?>
                        <?php if (empty($navCategory['slug']) || empty($navCategory['name'])) continue; ?>
                        <li class="menu-item">
                            <a href="category.php?slug=<?= htmlspecialchars($navCategory['slug']) ?>">
                                <?= htmlspecialchars($navCategory['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breaking News Ticker -->
    <div class="breaking-news-ticker">
        <div class="container">
            <div class="ticker-wrapper">
                <div class="ticker-label">À LA UNE</div>
                <div class="ticker-content">
                    <div class="ticker-swipe">
                        <?php foreach ($latestArticles as $latestArticle): ?>
                            <div class="ticker-item">
                                <a href="article.php?id=<?= $latestArticle['id'] ?>"><?= htmlspecialchars($latestArticle['title']) ?></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>