<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Vérifier si connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['journalist_id']) || strtolower($_SESSION['journalist_role']) !== 'journaliste') {
    header('Location: login.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filtres
$status = isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft']) ? $_GET['status'] : '';
$category = isset($_GET['category']) ? max(0, (int)$_GET['category']) : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Construire la requête
$where = ['a.journalist_id = ?'];
$params = [$_SESSION['journalist_id']];

if ($status) {
    $where[] = 'a.status = ?';
    $params[] = $status;
}
if ($category) {
    $where[] = 'a.category_id = ?';
    $params[] = $category;
}
if ($search) {
    $where[] = '(a.title LIKE ? OR a.content LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where);

// Compter le nombre total d'articles
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM articles a 
    WHERE $where_clause
");
$stmt->execute($params);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $per_page);

// Récupérer les articles
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.content, a.status, a.created_at, a.views, 
           c.name as category_name, a.journalist_id
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE $where_clause
    ORDER BY a.created_at DESC, a.id ASC
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pour le débogage
foreach ($articles as $article) {
    error_log("Article ID: " . $article['id'] . ", Title: " . $article['title'] . ", Journalist ID: " . $article['journalist_id']);
}

// Récupérer les catégories pour le filtre
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Au début du fichier, après les requires
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes articles - Espace Journaliste</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    
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
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">Actualités<span class="text-warning">GN</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="articles.php">Mes articles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_article.php">Nouvel article</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3"><?= htmlspecialchars($_SESSION['journalist_name']) ?></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Mes articles</h1>
            <a href="add_article.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nouvel article
            </a>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publié</option>
                            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category === $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Articles List -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($articles)): ?>
                    <p class="text-muted">Aucun article trouvé.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Catégorie</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Vues</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($article['title']) ?></td>
                                        <td><?= htmlspecialchars($article['category_name']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $article['status'] === 'published' ? 'success' : 'warning' ?>">
                                                <?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?>
                                            </span>
                                        </td>
                                        <td><?= formatDate($article['created_at']) ?></td>
                                        <td><?= $article['views'] ?></td>
                                        <td>
                                            <a href="edit_article.php?id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-primary edit-article" 
                                               data-id="<?= (int)$article['id'] ?>"
                                               data-title="<?= htmlspecialchars($article['title']) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="../article.php?id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-info view-article" 
                                               data-id="<?= (int)$article['id'] ?>"
                                               target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($article['status'] === 'draft'): ?>
                                            <a href="publish_article.php?id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-success publish-article"
                                               data-id="<?= (int)$article['id'] ?>"
                                               data-title="<?= htmlspecialchars($article['title']) ?>">
                                                <i class="bi bi-globe"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($article['status'] === 'published'): ?>
                                            <a href="unpublish_article.php?id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-warning unpublish-article"
                                               data-id="<?= (int)$article['id'] ?>"
                                               data-title="<?= htmlspecialchars($article['title']) ?>">
                                                <i class="bi bi-file-earmark"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="delete_article.php?id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-danger delete-article"
                                               data-id="<?= (int)$article['id'] ?>"
                                               data-title="<?= htmlspecialchars($article['title']) ?>"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">
                                            <i class="bi bi-chevron-left"></i> Précédent
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . $status . '&category=' . $category . '&search=' . urlencode($search) . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor;
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&status=' . $status . '&category=' . $category . '&search=' . urlencode($search) . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">
                                            Suivant <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" class="btn btn-danger" id="confirmDelete">Supprimer</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation d'édition -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'article</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir modifier cet article ?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" class="btn btn-primary" id="confirmEdit">Modifier</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de publication -->
    <div class="modal fade" id="publishModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Publier l'article</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir publier cet article ? Il sera visible par tous les visiteurs.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" class="btn btn-success" id="confirmPublish">Publier</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de dépublier -->
    <div class="modal fade" id="unpublishModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dépublier l'article</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir dépublier cet article ? Il ne sera plus visible par les visiteurs.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" class="btn btn-warning" id="confirmUnpublish">Dépublier</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages flash -->
    <?php if (isset($flash_message)): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-<?= $flash_type ?> text-white">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <?= htmlspecialchars($flash_message) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation des modals
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const publishModal = new bootstrap.Modal(document.getElementById('publishModal'));
            const unpublishModal = new bootstrap.Modal(document.getElementById('unpublishModal'));
            
            // Gestion de la suppression
            document.querySelectorAll('.delete-article').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const articleId = this.dataset.id;
                    const articleTitle = this.dataset.title;
                    const deleteUrl = this.href;
                    
                    document.querySelector('#deleteModal .modal-body').innerHTML = 
                        `Êtes-vous sûr de vouloir supprimer l'article "${articleTitle}" ? Cette action est irréversible.`;
                    
                    document.getElementById('confirmDelete').href = deleteUrl;
                    deleteModal.show();
                });
            });

            // Gestion de l'édition
            document.querySelectorAll('.edit-article').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const articleId = this.dataset.id;
                    const articleTitle = this.dataset.title;
                    const editUrl = this.href;
                    
                    document.querySelector('#editModal .modal-body').innerHTML = 
                        `Êtes-vous sûr de vouloir modifier l'article "${articleTitle}" ?`;
                    
                    document.getElementById('confirmEdit').href = editUrl;
                    editModal.show();
                });
            });

            // Gestion de la publication
            document.querySelectorAll('.publish-article').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const articleId = this.dataset.id;
                    const articleTitle = this.dataset.title;
                    const publishUrl = this.href;
                    
                    document.querySelector('#publishModal .modal-body').innerHTML = 
                        `Êtes-vous sûr de vouloir publier l'article "${articleTitle}" ? Il sera visible par tous les visiteurs.`;
                    
                    document.getElementById('confirmPublish').href = publishUrl;
                    publishModal.show();
                });
            });

            // Gestion de la dépublier
            document.querySelectorAll('.unpublish-article').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const articleId = this.dataset.id;
                    const articleTitle = this.dataset.title;
                    const unpublishUrl = this.href;
                    
                    document.querySelector('#unpublishModal .modal-body').innerHTML = 
                        `Êtes-vous sûr de vouloir dépublier l'article "${articleTitle}" ? Il ne sera plus visible par les visiteurs.`;
                    
                    document.getElementById('confirmUnpublish').href = unpublishUrl;
                    unpublishModal.show();
                });
            });

            // Auto-hide des messages flash après 5 secondes
            const toast = document.querySelector('.toast');
            if (toast) {
                setTimeout(() => {
                    toast.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html> 