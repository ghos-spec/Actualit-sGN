<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once 'includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obtenir les compteurs pour le tableau de bord
$articlesCount = getCount($conn, 'articles');
$journalistsCount = getCount($conn, 'journalists');
$categoriesCount = getCount($conn, 'categories');
$commentsCount = getCount($conn, 'comments');

// Obtenir les articles récents
$recentArticles = getRecentArticles($conn, 5);

// Obtenir les commentaires en attente
$pendingComments = getPendingComments($conn, 5);

include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tableau de bord</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Tableau de bord</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Info boxes -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="bi bi-file-text"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Articles</span>
                            <span class="info-box-number"><?= $articlesCount ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="bi bi-people"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Journalistes</span>
                            <span class="info-box-number"><?= $journalistsCount ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="bi bi-folder"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Catégories</span>
                            <span class="info-box-number"><?= $categoriesCount ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="bi bi-chat-dots"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Commentaires</span>
                            <span class="info-box-number"><?= $commentsCount ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Recent Articles -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Articles récents</h3>
                            <div class="card-tools">
                                <a href="articles.php" class="btn btn-sm btn-primary">Voir tous</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Catégorie</th>
                                        <th>Auteur</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentArticles as $article): ?>
                                    <tr>
                                        <td>
                                            <a href="edit_article.php?id=<?= $article['id'] ?>">
                                                <?= htmlspecialchars(limitWords($article['title'], 8)) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($article['category_name']) ?></td>
                                        <td><?= htmlspecialchars($article['journalist_name']) ?></td>
                                        <td><?= formatDate($article['created_at']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusBadgeClass($article['status']) ?>">
                                                <?= ucfirst($article['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Pending Comments -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Commentaires en attente</h3>
                            <div class="card-tools">
                                <a href="comments.php" class="btn btn-sm btn-primary">Voir tous</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($pendingComments)): ?>
                                <div class="text-center p-3">
                                    <p>Aucun commentaire en attente</p>
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($pendingComments as $comment): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($comment['author_name'] ?? $comment['journalist_name'] ?? '') ?></strong>
                                                    <small class="text-muted d-block">
                                                        Sur: <?= htmlspecialchars(limitWords($comment['article_title'], 5)) ?>
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <?= formatDate($comment['created_at']) ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <a href="comments.php?action=approve&id=<?= $comment['id'] ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check"></i>
                                                    </a>
                                                    <a href="comments.php?action=reject&id=<?= $comment['id'] ?>" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-x"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Actions rapides</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <a href="add_article.php" class="btn btn-primary btn-block mb-3">
                                        <i class="bi bi-plus-circle"></i> Nouvel article
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="add_journalist.php" class="btn btn-success btn-block mb-3">
                                        <i class="bi bi-person-plus"></i> Nouveau journaliste
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="add_category.php" class="btn btn-warning btn-block">
                                        <i class="bi bi-folder-plus"></i> Nouvelle catégorie
                                    </a>
                                </div>
                                <div class="col-6">
                                    <?php if (isSuperAdmin($conn)): ?>
                                        <a href="../superadmin/users.php" class="btn btn-info btn-block">
                                            <i class="bi bi-person-badge"></i> Gérer les utilisateurs admin
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?>