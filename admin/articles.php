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

// Traiter la suppression d'un article
if (isset($_POST['delete_article'])) {
    $article_id = (int)$_POST['article_id'];
    try {
        // Supprimer l'article
        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);
        
        // Journaliser l'action
        logAdminAction($conn, $_SESSION['admin_id'], 'delete_article', "Article ID: $article_id supprimé");
        
        $_SESSION['success'] = "L'article a été supprimé avec succès.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la suppression de l'article : " . $e->getMessage();
    }
    header('Location: articles.php');
    exit;
}

// Récupérer tous les articles avec leurs catégories et journalistes
$stmt = $conn->prepare("
    SELECT a.*, c.name as category_name, j.name as journalist_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN journalists j ON a.journalist_id = j.id 
    ORDER BY a.created_at DESC
");
$stmt->execute();
$articles = $stmt->fetchAll();

include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestion des Articles</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Articles</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Liste des Articles</h3>
                    <div class="card-tools">
                        <a href="add_article.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus"></i> Nouvel Article
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>Catégorie</th>
                                    <th>Journaliste</th>
                                    <th>Statut</th>
                                    <th>Date de création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                    <tr>
                                        <td><?= $article['id'] ?></td>
                                        <td><?= htmlspecialchars($article['title']) ?></td>
                                        <td><?= htmlspecialchars($article['category_name'] ?? 'Non catégorisé') ?></td>
                                        <td><?= htmlspecialchars($article['journalist_name'] ?? 'Non assigné') ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusBadgeClass($article['status']) ?>">
                                                <?= ucfirst($article['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($article['created_at'])) ?></td>
                                        <td>
                                            <a href="edit_article.php?id=<?= $article['id'] ?>" class="btn btn-info btn-sm">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="articles.php" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');">
                                                <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                                                <button type="submit" name="delete_article" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <a href="../article.php?id=<?= $article['id'] ?>" target="_blank" class="btn btn-secondary btn-sm">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 