<?php
ini_set('display_errors', 0);
ob_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Récupération du slug de la catégorie
$categorySlug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';
file_put_contents('debug.log', "Slug de la catégorie reçu : " . $categorySlug . "\n", FILE_APPEND);

// Récupération des informations de la catégorie
$category = getCategoryBySlug($conn, $categorySlug);
file_put_contents('debug.log', "Catégorie récupérée : " . print_r($category, true) . "\n", FILE_APPEND);

if (!$category) {
    file_put_contents('debug.log', "Aucune catégorie trouvée pour le slug : " . $categorySlug . "\n", FILE_APPEND);
    header('Location: index.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Récupération des articles de la catégorie
$articles = getArticlesByCategory($conn, $category['id'], $perPage, $offset);
error_log("Articles récupérés pour la catégorie : " . print_r($articles, true));

// Récupération du nombre total d'articles pour la pagination
$totalArticles = getCategoryArticleCount($conn, $category['id']);
$totalPages = ceil($totalArticles / $perPage);

// Récupération du titre du site
$siteTitle = getSiteTitle($conn);
$pageTitle = htmlspecialchars($category['name']) . " - " . $siteTitle;
error_log("Titre de la page généré : " . $pageTitle);

// DEBUG MARKER BEFORE HEADER
include 'includes/header.php';
?>

<div class="container py-4">
    <!-- En-tête de la catégorie -->
    <div class="category-header mb-4">
        <?php
        file_put_contents('debug.log', "DEBUG H1 - Valeur de \$category[\'name\'] avant affichage du titre : " . ($category['name'] ?? 'NULL') . "\n", FILE_APPEND);
        ?>
        <!-- DEBUG MARKER H1 -->
        <h1 class="category-title"><?= htmlspecialchars($category['name']) ?></h1>
        <?php
        file_put_contents('debug.log', "DEBUG AFTER H1 - Valeur de \$category[\'name\'] après affichage du titre : " . ($category['name'] ?? 'NULL') . "\n", FILE_APPEND);
        ?>
        <?php if (!empty($category['description'])): ?>
        <p class="category-description"><?= htmlspecialchars($category['description']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Liste des articles -->
    <div class="row">
        <?php if (!empty($articles)): ?>
            <?php foreach ($articles as $article): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if (!empty($article['image_path'])): ?>
                    <img src="<?= htmlspecialchars($article['image_path']) ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title h5">
                            <a href="article.php?id=<?= $article['id'] ?>" class="text-dark text-decoration-none">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h2>
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
                        <div class="article-meta mt-2">
                            <span class="badge bg-primary"><?= htmlspecialchars($category['name']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    Aucun article n'a été trouvé dans cette catégorie.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Navigation des pages" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?slug=<?= $categorySlug ?>&page=<?= $page - 1 ?>" aria-label="Précédent">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?slug=<?= $categorySlug ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?slug=<?= $categorySlug ?>&page=<?= $page + 1 ?>" aria-label="Suivant">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>