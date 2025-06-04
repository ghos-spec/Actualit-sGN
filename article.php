<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Fonction pour logger dans un fichier local avec UTF-8
function logToFile($message) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] " . (is_array($message) ? json_encode($message, JSON_UNESCAPED_UNICODE) : $message) . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log de l'URL complète et des paramètres
logToFile("=== NOUVELLE REQUÊTE ===");
logToFile("URL complète: " . $_SERVER['REQUEST_URI']);
logToFile("Méthode: " . $_SERVER['REQUEST_METHOD']);
logToFile("GET params: " . json_encode($_GET, JSON_UNESCAPED_UNICODE));
logToFile("Session data: " . json_encode($_SESSION, JSON_UNESCAPED_UNICODE));

// Obtenir l'ID de l'article depuis l'URL
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

logToFile("Tentative d'accès à l'article - ID: " . $articleId);

// Rediriger si l'ID est invalide ou si l'article n'est pas trouvé
if ($articleId <= 0 || !($article = getArticleById($conn, $articleId, isset($_SESSION['journalist_id']) ? (int)$_SESSION['journalist_id'] : null))) {
    logToFile("Article non trouvé ou ID invalide - Redirection vers index.php");
    header('Location: index.php');
    exit;
}

logToFile("Résultat de getArticleById pour ID " . $articleId . ": " . json_encode($article, JSON_UNESCAPED_UNICODE));

// Ajout de logs supplémentaires
logToFile("Valeur de \$article juste après getArticleById: " . json_encode($article, JSON_UNESCAPED_UNICODE));
logToFile("ID de l'article dans \$article: " . $article['id']);
logToFile("Titre de l'article dans \$article: " . $article['title']);

// Mettre à jour le compteur de vues
updateArticleViews($conn, $articleId);

// Récupérer les articles connexes
$relatedArticles = getRelatedArticles($conn, $articleId, $article['category_id'], 3);

// Ajout de logs supplémentaires après getRelatedArticles
logToFile("Valeur de \$article après getRelatedArticles: " . json_encode($article, JSON_UNESCAPED_UNICODE));

// Obtenir le titre du site et le titre de la page
$siteTitle = getSiteTitle($conn);
$pageTitle = htmlspecialchars($article['title']) . " - " . $siteTitle;

// Ajout de logs supplémentaires avant l'inclusion du header
logToFile("Valeur de \$article avant include header: " . json_encode($article, JSON_UNESCAPED_UNICODE));

include 'includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <!-- Main Article Content -->
        <div class="col-lg-8">
            <?php logToFile("Contenu de \$article juste avant l'affichage: " . json_encode($article, JSON_UNESCAPED_UNICODE)); ?>
            <article class="single-article">
                <div class="article-header">
                    <div class="article-category">
                        <a href="category.php?slug=<?= htmlspecialchars($article['category_slug'] ?? '') ?>" class="category-badge"><?= htmlspecialchars($article['category_name'] ?? 'Non catégorisé') ?></a>
                    </div>
                    <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                    <div class="article-meta">
                        <div class="journalist-info">
                            <img src="<?= !empty($article['journalist_avatar']) ? $article['journalist_avatar'] : 'assets/img/default-avatar.png' ?>" 
                                 alt="<?= htmlspecialchars($article['journalist_name'] ?? '') ?>" 
                                 class="journalist-avatar"
                                 width="32"
                                 height="32">
                            <span class="journalist-name">Par <?= htmlspecialchars($article['journalist_name'] ?? '') ?></span>
                        </div>
                        <div class="article-date">Publié le <?= formatDate($article['published_date'], true) ?></div>
                    </div>
                </div>

                <?php if ($article['image_path']): ?>
                <div class="article-featured-image">
                    <img src="<?= htmlspecialchars($article['image_path']) ?>?v=<?= strtotime($article['updated_at'] ?? $article['created_at']) ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>">
                    <?php if ($article['image_caption']): ?>
                    <div class="image-caption"><?= htmlspecialchars($article['image_caption']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($article['video_url']): ?>
                <div class="article-video mb-4">
                    <div class="ratio ratio-16x9">
                        <?php if (strpos($article['video_url'], 'youtube.com') !== false || strpos($article['video_url'], 'youtu.be') !== false): ?>
                            <?php
                            // Extract YouTube video ID
                            preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $article['video_url'], $matches);
                            $youtubeId = $matches[1] ?? '';
                            ?>
                            <iframe src="https://www.youtube.com/embed/<?= $youtubeId ?>" title="YouTube video" allowfullscreen></iframe>
                        <?php else: ?>
                            <video controls>
                                <source src="<?= $article['video_url'] ?>" type="video/mp4">
                                Votre navigateur ne supporte pas la lecture de vidéos.
                            </video>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="article-content">
                    <?= $article['content'] ?>
                </div>

                <div class="article-tags mt-4">
                    <?php
                    if (!empty($article['tags'])) {
                        $tags = explode(',', $article['tags']);
                        foreach ($tags as $tag) {
                            echo '<a href="tag.php?t=' . urlencode(trim($tag)) . '" class="article-tag">' . htmlspecialchars(trim($tag)) . '</a>';
                        }
                    }
                    ?>
                </div>

                <div class="article-share mt-4">
                    <div class="share-title">Partager cet article:</div>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(getCurrentUrl()) ?>" target="_blank" class="btn btn-sm btn-facebook"><i class="bi bi-facebook"></i> Facebook</a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(getCurrentUrl()) ?>&text=<?= urlencode($article['title']) ?>" target="_blank" class="btn btn-sm btn-twitter"><i class="bi bi-twitter"></i> Twitter</a>
                        <a href="https://wa.me/?text=<?= urlencode($article['title'] . ' ' . getCurrentUrl()) ?>" target="_blank" class="btn btn-sm btn-whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                    </div>
                </div>
            </article>

            <!-- Journalist Bio -->
            <div class="journalist-bio mt-4">
                <div class="bio-header">
                    <img src="<?= !empty($article['journalist_avatar']) ? $article['journalist_avatar'] : 'assets/img/default-avatar.png' ?>" alt="<?= htmlspecialchars($article['journalist_name'] ?? '') ?>" class="bio-avatar">
                    <div class="bio-info">
                        <h4 class="bio-name"><?= htmlspecialchars($article['journalist_name'] ?? '') ?></h4>
                        <div class="bio-title"><?= htmlspecialchars($article['journalist_title'] ?? 'Journaliste') ?></div>
                    </div>
                </div>
                <div class="bio-content">
                    <?= $article['journalist_bio'] ?? 'Journaliste chez ' . $siteTitle ?>
                </div>
            </div>

            <!-- Comment Section -->
            <div class="comments-section mt-4">
                <h3 class="sidebar-title">Commentaires</h3>
                
                <div class="comments-count">
                    <?php
                    // Check if a journalist is logged in and get their ID
                    $loggedInJournalistId = isset($_SESSION['journalist_id']) ? (int)$_SESSION['journalist_id'] : null;

                    // Get total comments, including pending for the article owner journalist
                    $totalComments = getCommentCount($conn, $article['id'], null, $loggedInJournalistId);
                    echo $totalComments . ' commentaire' . ($totalComments > 1 ? 's' : '');
                    ?>
                </div>

                <!-- Comment Form -->
                <div class="comment-form mb-4">
                    <h4>Laisser un commentaire</h4>
                    <div class="moderation-notice">
                        Les commentaires sont modérés avant publication. Merci de respecter les règles de bonne conduite.
                    </div>
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
                        $authorName = sanitizeInput($_POST['author_name'] ?? '');
                        $authorEmail = sanitizeInput($_POST['author_email'] ?? '');
                        $commentContent = sanitizeInput($_POST['comment_content'] ?? '');
                        $articleIdForComment = (int)$_POST['article_id'];

                        if (!empty($authorName) && !empty($commentContent) && $articleIdForComment > 0) {
                            if (addComment($conn, $articleIdForComment, $authorName, $authorEmail, $commentContent)) {
                                // Stocker le message de succès dans la session
                                $_SESSION['comment_message'] = 'Votre commentaire a été soumis et est en attente de modération.';
                                // Utiliser JavaScript pour la redirection
                                echo '<script>window.location.href = "article.php?id=' . $articleIdForComment . '";</script>';
                                exit;
                            } else {
                                $_SESSION['comment_error'] = 'Une erreur est survenue lors de la soumission du commentaire.';
                                echo '<script>window.location.href = "article.php?id=' . $articleIdForComment . '";</script>';
                                exit;
                            }
                        } else {
                            $_SESSION['comment_error'] = 'Veuillez remplir tous les champs obligatoires (Nom et Commentaire).';
                            echo '<script>window.location.href = "article.php?id=' . $articleIdForComment . '";</script>';
                            exit;
                        }
                    }

                    // Afficher les messages stockés en session
                    if (isset($_SESSION['comment_message'])) {
                        echo '<div class="alert alert-success" role="alert">' . $_SESSION['comment_message'] . '</div>';
                        unset($_SESSION['comment_message']); // Supprimer le message après l'affichage
                    }
                    if (isset($_SESSION['comment_error'])) {
                        echo '<div class="alert alert-danger" role="alert">' . $_SESSION['comment_error'] . '</div>';
                        unset($_SESSION['comment_error']); // Supprimer le message après l'affichage
                    }
                    ?>
                    <form action="#" method="POST">
                        <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                        <div class="mb-3">
                            <label for="author_name" class="form-label">Votre nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author_name" name="author_name" required placeholder="Entrez votre nom">
                        </div>
                        <div class="mb-3">
                            <label for="author_email" class="form-label">Votre email (optionnel)</label>
                            <input type="email" class="form-control" id="author_email" name="author_email" placeholder="Entrez votre email">
                        </div>
                        <div class="mb-3">
                            <label for="comment_content" class="form-label">Votre commentaire <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="comment_content" name="comment_content" rows="4" required placeholder="Écrivez votre commentaire ici..."></textarea>
                        </div>
                        <button type="submit" name="submit_comment" class="btn btn-primary">Publier le commentaire</button>
                    </form>
                </div>

                <!-- List of Comments -->
                <div class="comments-list" data-article-id="<?= $article['id'] ?>" data-current-page="1" data-comments-per-page="5">
                    <?php
                    // Check if a journalist is logged in and get their ID
                    $loggedInJournalistId = isset($_SESSION['journalist_id']) ? (int)$_SESSION['journalist_id'] : null;

                    // Determine status filter based on user role
                    $commentStatusFilter = ($loggedInJournalistId !== null && getArticleById($conn, $article['id'])['journalist_id'] == $loggedInJournalistId) ? null : 'approved';

                    // Get initial set of comments
                    $initialComments = getCommentsByArticleId($conn, $article['id'], $commentStatusFilter, null, 1, 5, $loggedInJournalistId);
                    
                    if ($initialComments) {
                        foreach ($initialComments as $comment) {
                            $commentClass = $comment['status'] === 'pending' ? 'comment-item pending' : 'comment-item';
                    ?>
                            <div class="<?= $commentClass ?>" id="comment-<?= $comment['id'] ?>">
                                <div class="comment-author-info">
                                     <i class="bi bi-person-circle"></i>
                                     <span><?= htmlspecialchars($comment['author_name'] ?? '') ?></span>
                                </div>
                                <div class="comment-meta">
                                    <i class="bi bi-clock"></i>
                                     <span>Le <?= formatDate($comment['created_at']) ?></span>
                                     <?php if ($commentStatusFilter === null): // Only show status to journalist/admin ?>
                                        - Statut: <span class="status-badge status-<?= $comment['status'] ?>"><?= ucfirst($comment['status']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($comment['author_email'])): // Optional: Keep email but maybe style it differently later ?>
                                     <div class="comment-email" style="font-size: 0.8rem; color: #6c757d; margin-bottom: 5px;">
                                        <i class="bi bi-envelope"></i>
                                         <?= htmlspecialchars($comment['author_email']) ?>
                                     </div>
                                <?php endif; ?>
                                <div class="comment-content">
                                    <?= nl2br(htmlspecialchars($comment['content'] ?? '')) ?>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo '<p>Aucun commentaire n\'a encore été publié. Soyez le premier à commenter !</p>';
                    }
                    ?>
                </div>

                <?php 
                // Re-calculate total comments for the load more button check, considering journalist view
                $totalCommentsForLoadMore = getCommentCount($conn, $article['id'], $commentStatusFilter, $loggedInJournalistId);
                
                if ($totalCommentsForLoadMore > 5): // Show button if there are more comments than the initial load ?>
                    <button id="load-more-comments" class="btn btn-secondary btn-sm mt-3">Charger plus de commentaires</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Related Articles -->
            <div class="sidebar-section">
                <h3 class="sidebar-title">Articles similaires</h3>
                <div class="related-articles">
                    <?php foreach ($relatedArticles as $related): ?>
                    <div class="related-article">
                        <a href="article.php?id=<?= $related['id'] ?>" class="related-image">
                            <img src="<?= $related['image_path'] ?>" alt="<?= htmlspecialchars($related['title']) ?>" class="img-fluid">
                        </a>
                        <div class="related-content">
                            <h4 class="related-title"><a href="article.php?id=<?= $related['id'] ?>"><?= htmlspecialchars($related['title']) ?></a></h4>
                            <div class="related-date"><?= formatDate($related['published_date']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Most Popular -->
            <div class="sidebar-section">
                <h3 class="sidebar-title">Les plus lus</h3>
                <div class="popular-articles">
                    <?php 
                    $popularArticles = getPopularArticles($conn, 5);
                    foreach ($popularArticles as $index => $popular): 
                    ?>
                    <div class="popular-article">
                        <span class="popular-number"><?= $index + 1 ?></span>
                        <div class="popular-content">
                            <h4 class="popular-title"><a href="article.php?id=<?= $popular['id'] ?>"><?= htmlspecialchars($popular['title']) ?></a></h4>
                            <div class="popular-date"><?= formatDate($popular['published_date']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreButton = document.getElementById('load-more-comments');
    const commentsList = document.querySelector('.comments-list');

    if (loadMoreButton && commentsList) {
        loadMoreButton.addEventListener('click', function() {
            const articleId = commentsList.dataset.articleId;
            let currentPage = parseInt(commentsList.dataset.currentPage);
            const commentsPerPage = parseInt(commentsList.dataset.commentsPerPage);
            
            currentPage++; // Increment page number

            // Show a loading indicator (optional)
            loadMoreButton.textContent = 'Chargement...';
            loadMoreButton.disabled = true;

            fetch(`get_comments.php?article_id=${articleId}&page=${currentPage}&per_page=${commentsPerPage}`)
                .then(response => response.json())
                .then(data => {
                    if (data.html) {
                        commentsList.insertAdjacentHTML('beforeend', data.html);
                        commentsList.dataset.currentPage = currentPage; // Update current page
                    }

                    if (!data.hasMore) {
                        loadMoreButton.style.display = 'none'; // Hide button if no more comments
                    } else {
                         loadMoreButton.textContent = 'Charger plus de commentaires';
                         loadMoreButton.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des commentaires:', error);
                    loadMoreButton.textContent = 'Erreur';
                    loadMoreButton.disabled = false;
                });
        });
    }
});
</script>