<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Obtenir la requête de recherche
$searchQuery = sanitizeInput($_GET['q'] ?? '');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Résultats de recherche
$articles = [];
$totalResults = 0;

if (!empty($searchQuery)) {
    // Effectuer la recherche
    $articles = searchArticles($conn, $searchQuery, $offset, $perPage);
    $totalResults = countSearchResults($conn, $searchQuery);
}

$totalPages = ceil($totalResults / $perPage);

// Obtenir le titre du site depuis les paramètres
$siteTitle = getSiteTitle($conn);

$pageTitle = "Résultats de recherche pour \"$searchQuery\" - $siteTitle";
include 'includes/header.php';
?>

<div class="container my-4">
    <div class="search-header">
        <h1 class="search-title">Résultats de recherche</h1>
        <div class="search-form mb-4">
            <form action="search.php" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Rechercher des articles...">
                    <button class="btn btn-primary" type="submit">Rechercher</button>
                </div>
            </form>
        </div>
        
        <?php if (!empty($searchQuery)): ?>
            <p class="search-results-count"><?= $totalResults ?> résultat(s) trouvé(s) pour "<?= htmlspecialchars($searchQuery) ?>"</p>
        <?php endif; ?>
    </div>

    <?php if (empty($searchQuery)): ?>
        <div class="search-empty text-center my-5">
            <h3>Veuillez saisir un terme de recherche</h3>
            <p>Entrez des mots-clés pour trouver des articles pertinents.</p>
        </div>
    <?php elseif (empty($articles)): ?>
        <div class="search-empty text-center my-5">
            <h3>Aucun résultat trouvé</h3>
            <p>Votre recherche "<?= htmlspecialchars($searchQuery) ?>" n'a donné aucun résultat.</p>
            <p>Suggestions :</p>
            <ul class="list-unstyled">
                <li>Vérifiez l'orthographe des termes de recherche.</li>
                <li>Essayez des termes plus généraux.</li>
                <li>Essayez des termes différents.</li>
            </ul>
        </div>
    <?php else: ?>
        <div class="search-results">
            <?php foreach ($articles as $article): ?>
                <div class="search-result-item">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="article.php?id=<?= $article['id'] ?>" class="result-image">
                                <img src="<?= $article['image_path'] ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="img-fluid">
                            </a>
                        </div>
                        <div class="col-md-9">
                            <div class="result-content">
                                <div class="result-category">
                                    <a href="category.php?slug=<?= htmlspecialchars($article['category_slug']) ?>" class="category-badge"><?= htmlspecialchars($article['category_name']) ?></a>
                                </div>
                                <h3 class="result-title"><a href="article.php?id=<?= $article['id'] ?>"><?= htmlspecialchars($article['title']) ?></a></h3>
                                <p class="result-excerpt"><?= limitWords(htmlspecialchars($article['excerpt']), 30) ?></p>
                                <div class="article-meta">
                                    <span class="article-author">Par <?= htmlspecialchars($article['journalist_name']) ?></span>
                                    <span class="article-date"><?= formatDate($article['published_date']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Search results pagination">
            <ul class="pagination justify-content-center mt-4">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="search.php?q=<?= urlencode($searchQuery) ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="search.php?q=<?= urlencode($searchQuery) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="search.php?q=<?= urlencode($searchQuery) ?>&page=<?= $page + 1 ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>