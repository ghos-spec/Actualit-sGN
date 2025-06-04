<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Vérifier si connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['journalist_id']) || $_SESSION['journalist_role'] !== 'journaliste') {
    header('Location: login.php');
    exit;
}

// Récupérer les statistiques des articles du journaliste
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_articles,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_articles,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_articles,
        SUM(views) as total_views
    FROM articles 
    WHERE journalist_id = ?
");
$stmt->execute([$_SESSION['journalist_id']]);
$stats = $stmt->fetch();

// Récupérer les derniers articles
$stmt = $conn->prepare("
    SELECT a.*, c.name as category_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE a.journalist_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['journalist_id']]);
$recent_articles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Espace Journaliste</title>
    
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
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #007bff;
        }
        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #6c757d;
            margin: 0;
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
                        <a class="nav-link active" href="index.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="articles.php">Mes articles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_article.php">Nouvel article</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comments.php">Commentaires</a>
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
        <h1 class="mb-4">Tableau de bord</h1>
        
        <!-- Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-file-text"></i>
                    <h3><?= $stats['total_articles'] ?></h3>
                    <p>Articles totaux</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-check-circle"></i>
                    <h3><?= $stats['published_articles'] ?></h3>
                    <p>Articles publiés</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-pencil"></i>
                    <h3><?= $stats['draft_articles'] ?></h3>
                    <p>Brouillons</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-eye"></i>
                    <h3><?= $stats['total_views'] ?></h3>
                    <p>Vues totales</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Articles -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Derniers articles</h5>
                <a href="articles.php" class="btn btn-primary btn-sm">Voir tous</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_articles)): ?>
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
                                <?php foreach ($recent_articles as $article): ?>
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
                                            <a href="edit_article.php?id=<?= $article['id'] ?>" class="btn btn-sm btn-primary edit-article" 
                                               data-id="<?= $article['id'] ?>"
                                               data-title="<?= htmlspecialchars($article['title']) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="../article.php?id=<?= $article['id'] ?>" class="btn btn-sm btn-info" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation du modal
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            
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
        });
    </script>
</body>
</html> 