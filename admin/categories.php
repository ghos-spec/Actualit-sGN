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

// Traiter la suppression d'une catégorie
if (isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    try {
        // Vérifier si la catégorie a des articles
        $stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
        $stmt->execute([$category_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Impossible de supprimer cette catégorie car elle contient des articles.");
        }

        // Supprimer la catégorie
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        
        // Journaliser l'action
        logAdminAction($conn, $_SESSION['admin_id'], 'delete_category', "Catégorie ID: $category_id supprimée");
        
        $_SESSION['success'] = "La catégorie a été supprimée avec succès.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la suppression de la catégorie : " . $e->getMessage();
    }
    header('Location: categories.php');
    exit;
}

// Récupérer toutes les catégories avec leurs statistiques
$stmt = $conn->prepare("
    SELECT c.*, 
           COUNT(a.id) as total_articles,
           SUM(CASE WHEN a.status = 'published' THEN 1 ELSE 0 END) as published_articles
    FROM categories c
    LEFT JOIN articles a ON c.id = a.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestion des Catégories</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Catégories</li>
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
                    <h3 class="card-title">Liste des Catégories</h3>
                    <div class="card-tools">
                        <a href="add_category.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus"></i> Nouvelle Catégorie
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th>Articles</th>
                                    <th>Publiés</th>
                                    <th>Date d'ajout</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= $category['id'] ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><?= htmlspecialchars($category['slug']) ?></td>
                                        <td><?= htmlspecialchars($category['description'] ?? '') ?></td>
                                        <td><?= $category['total_articles'] ?></td>
                                        <td><?= $category['published_articles'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($category['created_at'])) ?></td>
                                        <td>
                                            <a href="edit_category.php?id=<?= $category['id'] ?>" class="btn btn-info btn-sm">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="categories.php" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                <button type="submit" name="delete_category" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <a href="../category.php?slug=<?= $category['slug'] ?>" target="_blank" class="btn btn-secondary btn-sm">
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