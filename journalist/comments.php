<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Log après les includes
error_log("DEBUG: journalist/comments.php - Tous les fichiers inclus avec succès.");

// Vérifier si connecté et rôle journaliste
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['journalist_id']) || $_SESSION['journalist_role'] !== 'journaliste') {
    header('Location: login.php');
    exit;
}

$journalistId = $_SESSION['journalist_id'];

// Récupérer les paramètres de filtrage et de pagination
$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at'; // Default sort by date
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'desc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;

// Récupérer le nombre total de commentaires pour les articles du journaliste (avec filtres)
$totalComments = countCommentsByJournalistArticles($conn, $journalistId, $statusFilter, $searchQuery);

// Calculer le nombre total de pages
$totalPages = ceil($totalComments / $perPage);

// Assurer que le numéro de page est valide
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// Récupérer les commentaires paginés, filtrés et triés
$comments = getCommentsByJournalistArticles(
    $conn,
    $journalistId,
    $statusFilter,
    $searchQuery,
    $sortBy,
    $sortOrder,
    $perPage,
    $offset
);

// Récupérer les statistiques complètes (sans pagination ni tri, mais avec le filtre de statut si appliqué)
$totalCommentsStat = countCommentsByJournalistArticles($conn, $journalistId, null, null); // Total sans filtre
$pendingCommentsStat = countCommentsByJournalistArticles($conn, $journalistId, 'pending', null);
$approvedCommentsStat = countCommentsByJournalistArticles($conn, $journalistId, 'approved', null);
$rejectedCommentsStat = countCommentsByJournalistArticles($conn, $journalistId, 'rejected', null);

$pageTitle = 'Gestion des commentaires - Espace Journaliste';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
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
        .comment-item {
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
        }
        .comment-author {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .comment-date {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .comment-content {
            font-size: 0.9rem;
        }
        .comment-actions button {
            margin-right: 5px;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-approved {
            background-color: #28a745;
            color: #fff;
        }
        .status-rejected {
            background-color: #dc3545;
            color: #fff;
        }
        .comment-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .sort-buttons {
            margin-bottom: 20px;
        }
        .sort-buttons .btn {
            margin-right: 5px;
        }
        .pagination {
            margin-top: 20px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 2px;
            border-radius: 2px;
        }
        .notification-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: none;
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
                        <a class="nav-link" href="articles.php">Mes articles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_article.php">Nouvel article</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link active" href="comments.php">Commentaires</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3"><?= htmlspecialchars($_SESSION['journalist_name']) ?></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Notification Badge -->
    <div class="notification-badge alert alert-info" role="alert">
        <i class="bi bi-bell"></i> Nouveaux commentaires disponibles
    </div>

    <!-- Main Content -->
    <div class="container py-4">
        <h1 class="mb-4">Gestion des commentaires de mes articles</h1>

        <?php if (empty($comments)): ?>
            <div class="alert alert-info" role="alert">
                Vous n'avez pas encore publié d'articles.
            </div>
        <?php else: ?>
            <!-- Barre de recherche -->
            <div class="search-box">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Rechercher dans les commentaires..." value="<?= htmlspecialchars($searchQuery) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end">
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filtres de statut -->
            <div class="filter-buttons mb-4">
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => null, 'page' => 1])) ?>" 
                   class="btn btn-outline-secondary <?= $statusFilter === null ? 'active' : '' ?>">
                    Tous
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'pending', 'page' => 1])) ?>" 
                   class="btn btn-outline-warning <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                    En attente
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'approved', 'page' => 1])) ?>" 
                   class="btn btn-outline-success <?= $statusFilter === 'approved' ? 'active' : '' ?>">
                    Approuvés
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'rejected', 'page' => 1])) ?>" 
                   class="btn btn-outline-danger <?= $statusFilter === 'rejected' ? 'active' : '' ?>">
                    Rejetés
                </a>
            </div>

            <!-- Boutons de tri -->
            <div class="sort-buttons">
                <div class="btn-group">
                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'date', 'order' => $sortBy === 'date' && $sortOrder === 'asc' ? 'desc' : 'asc', 'page' => 1])) ?>" 
                       class="btn btn-outline-secondary <?= $sortBy === 'date' ? 'active' : '' ?>">
                        <i class="bi bi-calendar"></i> Date
                        <?php if ($sortBy === 'date'): ?>
                            <i class="bi bi-arrow-<?= $sortOrder === 'asc' ? 'up' : 'down' ?>"></i>
                        <?php endif; ?>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => $sortBy === 'status' && $sortOrder === 'asc' ? 'desc' : 'asc', 'page' => 1])) ?>" 
                       class="btn btn-outline-secondary <?= $sortBy === 'status' ? 'active' : '' ?>">
                        <i class="bi bi-funnel"></i> Statut
                        <?php if ($sortBy === 'status'): ?>
                            <i class="bi bi-arrow-<?= $sortOrder === 'asc' ? 'up' : 'down' ?>"></i>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Statistiques des commentaires -->
            <div class="comment-stats">
                <div class="stat-item">
                    <div class="stat-number"><?= $totalCommentsStat ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $pendingCommentsStat ?></div>
                    <div class="stat-label">En attente</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $approvedCommentsStat ?></div>
                    <div class="stat-label">Approuvés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $rejectedCommentsStat ?></div>
                    <div class="stat-label">Rejetés</div>
                </div>
            </div>

            <?php 
            if (!empty($comments)):
            ?>
                <?php 
                // Afficher les commentaires directement
                foreach ($comments as $comment): 
            ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Commentaire sur l'article : <?= htmlspecialchars($comment['article_title']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="comments-list">
                                <div class="comment-item" id="comment-<?= $comment['id'] ?>">
                                    <div class="comment-author"><?= htmlspecialchars($comment['author_name'] ?? '') ?></div>
                                    <?php if (!empty($comment['author_email'])): ?>
                                        <div class="comment-email text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($comment['author_email']) ?></div>
                                    <?php endif; ?>
                                    <div class="comment-date">
                                        Le <?= formatDate($comment['created_at']) ?> - 
                                        Statut: <span id="status-<?= $comment['id'] ?>" class="status-badge status-<?= $comment['status'] ?>"><?= ucfirst($comment['status']) ?></span>
                                    </div>
                                    <div class="comment-content">
                                        <?php
                                        $content = htmlspecialchars($comment['content'] ?? '');
                                        if ($searchQuery) {
                                            $content = preg_replace('/(' . preg_quote($searchQuery, '/') . ')/i', '<span class="highlight">$1</span>', $content);
                                        }
                                        echo nl2br($content);
                                        ?>
                                    </div>
                                    <!-- Actions pour gérer les commentaires -->
                                    <div class="comment-actions mt-2">
                                        <?php if ($comment['status'] === 'pending'): ?>
                                            <button class="btn btn-success btn-sm approve-comment" data-comment-id="<?= $comment['id'] ?>">Approuver</button>
                                            <button class="btn btn-warning btn-sm reject-comment" data-comment-id="<?= $comment['id'] ?>">Rejeter</button>
                                        <?php elseif ($comment['status'] === 'approved'): ?>
                                            <button class="btn btn-warning btn-sm reject-comment" data-comment-id="<?= $comment['id'] ?>">Rejeter</button>
                                        <?php elseif ($comment['status'] === 'rejected'): ?>
                                             <button class="btn btn-success btn-sm approve-comment" data-comment-id="<?= $comment['id'] ?>">Approuver</button>
                                        <?php endif; ?>
                                         <button class="btn btn-danger btn-sm delete-comment" data-comment-id="<?= $comment['id'] ?>">Supprimer</button>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach; 
            ?>
            <?php endif; ?>
            
            <?php if (empty($comments) && ($statusFilter || $searchQuery)): ?>
                <div class="alert alert-info" role="alert">
                    Aucun commentaire trouvé avec les filtres appliqués.
                </div>
            <?php elseif (empty($comments) && !$statusFilter && !$searchQuery): ?>
                 <div class="alert alert-info" role="alert">
                    Aucun commentaire pour l'instant sur vos articles.
                </div>
            <?php endif; ?>

            <?php 
            // Pagination
            if ($totalPages > 1):
            ?>
            <nav aria-label="Pagination des commentaires">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="bi bi-chevron-left"></i> Précédent
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                Suivant <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gérer l'approbation, le rejet et la suppression des commentaires via AJAX
        document.querySelectorAll('.approve-comment, .reject-comment, .delete-comment').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const commentId = this.dataset.commentId;
                let action = '';
                if (this.classList.contains('approve-comment')) {
                    action = 'approve';
                } else if (this.classList.contains('reject-comment')) {
                    action = 'reject';
                } else if (this.classList.contains('delete-comment')) {
                    action = 'delete';
                    if (!confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
                        return; // Stop if user cancels
                    }
                }

                // Basic validation for action
                if (!action) {
                    console.error('Invalid action');
                    return;
                }

                fetch('comment_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `comment_id=${commentId}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the comment status displayed on the page
                        const statusSpan = document.getElementById(`status-${commentId}`);
                        if (statusSpan) {
                            // Update status badge
                            statusSpan.className = `status-badge status-${action === 'approve' ? 'approved' : (action === 'reject' ? 'rejected' : 'deleted')}`;
                            statusSpan.textContent = action === 'approve' ? 'Approved' : (action === 'reject' ? 'Rejected' : 'Deleted');
                            
                            // Optionally, hide the comment item if deleted
                            if (action === 'delete') {
                                const commentItem = document.getElementById(`comment-${commentId}`);
                                if(commentItem) commentItem.style.display = 'none';
                            }
                            
                            // Update action buttons based on new status
                            const actionsDiv = statusSpan.closest('.comment-item').querySelector('.comment-actions');
                            if (actionsDiv) {
                                if (action === 'approve') {
                                    actionsDiv.innerHTML = `
                                        <button class="btn btn-warning btn-sm reject-comment" data-comment-id="${commentId}">Rejeter</button>
                                        <button class="btn btn-danger btn-sm delete-comment" data-comment-id="${commentId}">Supprimer</button>
                                    `;
                                } else if (action === 'reject') {
                                    actionsDiv.innerHTML = `
                                        <button class="btn btn-success btn-sm approve-comment" data-comment-id="${commentId}">Approuver</button>
                                        <button class="btn btn-danger btn-sm delete-comment" data-comment-id="${commentId}">Supprimer</button>
                                    `;
                                }
                            }
                        }
                    } else {
                        alert('Erreur: ' + (data.error || 'Une erreur inconnue est survenue.'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la communication avec le serveur.');
                });
            });
        });

        // Vérifier les nouveaux commentaires toutes les 30 secondes
        let lastCheck = new Date().getTime();
        
        function checkNewComments() {
            fetch('check_new_comments.php')
                .then(response => response.json())
                .then(data => {
                    if (data.hasNewComments) {
                        const notificationBadge = document.querySelector('.notification-badge');
                        notificationBadge.style.display = 'block';
                        setTimeout(() => {
                            notificationBadge.style.display = 'none';
                        }, 5000);
                    }
                })
                .catch(error => console.error('Erreur lors de la vérification des nouveaux commentaires:', error));
        }

        // Vérifier les nouveaux commentaires toutes les 30 secondes
        setInterval(checkNewComments, 30000);
    });
    </script>

</body>
</html> 