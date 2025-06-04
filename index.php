<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Récupération des articles mis en avant
$featuredArticles = getFeaturedArticles($conn, 3);

// Récupération des dernières actualités
$latestArticles = getLatestArticles($conn, 6);

// Récupération des articles populaires
$popularArticles = getPopularArticles($conn, 4);

// Récupération des catégories principales
$categories = getAllCategories($conn);

// Récupération du titre du site
$siteTitle = getSiteTitle($conn);
$pageTitle = "Accueil - " . $siteTitle;

include 'includes/header.php';
?>

<div class="container-fluid px-0">
    <!-- Section des articles en vedette -->
    <?php if (!empty($featuredArticles)): ?>
    <section class="featured-articles py-4 bg-light">
        <div class="container">
            <h2 class="section-title mb-4">À la Une</h2>
            <div class="row">
                <?php foreach ($featuredArticles as $article): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($article['image_path'])): ?>
                        <img src="<?= htmlspecialchars($article['image_path']) ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="category-badge mb-2">
                                <a href="category.php?slug=<?= htmlspecialchars($article['category_slug'] ?? '') ?>">
                                    <?= htmlspecialchars($article['category_name'] ?? 'Non catégorisé') ?>
                                </a>
                            </div>
                            <h3 class="card-title h5">
                                <a href="article.php?id=<?= $article['id'] ?>" class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h3>
                            <p class="card-text">
                                <?= limitWords(htmlspecialchars($article['excerpt'] ?? ''), 20) ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Par <?= htmlspecialchars($article['journalist_name'] ?? '') ?>
                                </small>
                                <small class="text-muted">
                                    <?= formatDate($article['published_date']) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Section principale -->
    <div class="container py-4">
        <div class="row">
            <!-- Articles récents -->
            <div class="col-lg-8">
                <h2 class="section-title mb-4">Dernières Actualités</h2>
                <div class="row">
                    <?php foreach ($latestArticles as $article): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($article['image_path'])): ?>
                            <img src="<?= htmlspecialchars($article['image_path']) ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="category-badge mb-2">
                                    <a href="category.php?slug=<?= htmlspecialchars($article['category_slug'] ?? '') ?>">
                                        <?= htmlspecialchars($article['category_name'] ?? 'Non catégorisé') ?>
                                    </a>
                                </div>
                                <h3 class="card-title h5">
                                    <a href="article.php?id=<?= $article['id'] ?>" class="text-dark text-decoration-none">
                                        <?= htmlspecialchars($article['title']) ?>
                                    </a>
                                </h3>
                                <p class="card-text">
                                    <?= limitWords(htmlspecialchars($article['excerpt'] ?? ''), 15) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Par <?= htmlspecialchars($article['journalist_name'] ?? '') ?>
                                    </small>
                                    <small class="text-muted">
                                        <?= formatDate($article['published_date']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Catégories -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Catégories</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($categories as $category): ?>
                            <a href="category.php?slug=<?= htmlspecialchars($category['slug']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($category['name']) ?>
                                <span class="badge bg-primary rounded-pill">
                                    <?= getCategoryArticleCount($conn, $category['id']) ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Articles populaires -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Articles Populaires</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($popularArticles as $article): ?>
                        <div class="popular-article mb-3">
                            <h4 class="h6">
                                <a href="article.php?id=<?= $article['id'] ?>" class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h4>
                            <small class="text-muted">
                                <?= formatDate($article['published_date']) ?> • 
                                <?= $article['views'] ?> vues
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>