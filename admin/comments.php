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

$error = '';
$success = '';

// Gérer les actions (approuver, rejeter, supprimer)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $comment_id = (int)$_GET['id'];
    $action = sanitizeInput($_GET['action']);

    try {
        $stmt = null;
        $log_action = '';
        $log_details = '';
        $redirect_message = '';

        switch ($action) {
            case 'approve':
                $stmt = $conn->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
                $log_action = 'approve_comment';
                $log_details = 'Commentaire approuvé avec l\'ID: ' . $comment_id;
                $redirect_message = 'Commentaire approuvé avec succès.';
                break;
            case 'reject':
                $stmt = $conn->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
                $log_action = 'reject_comment';
                $log_details = 'Commentaire rejeté avec l\'ID: ' . $comment_id;
                $redirect_message = 'Commentaire rejeté avec succès.';
                break;
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
                $log_action = 'delete_comment';
                $log_details = 'Commentaire supprimé avec l\'ID: ' . $comment_id;
                $redirect_message = 'Commentaire supprimé avec succès.';
                break;
            default:
                $error = 'Action non valide.';
        }

        if ($stmt) {
            $stmt->execute([$comment_id]);
            if ($stmt->rowCount() > 0) {
                // Journaliser l'action
                 logAdminAction($conn, $_SESSION['admin_id'], $log_action, $log_details);
                // Rediriger pour éviter la réexécution de l'action
                header('Location: comments.php?success=' . urlencode($redirect_message));
                exit;
            } else {
                $error = 'Commentaire non trouvé ou action déjà effectuée.';
            }
        }

    } catch (Exception $e) {
        $error = 'Une erreur est survenue : ' . $e->getMessage();
         logAdminAction($conn, $_SESSION['admin_id'], $log_action . '_error', 'Erreur: ' . $e->getMessage());
    }
}

// Récupérer les messages de succès ou d'erreur de la redirection
if (isset($_GET['success'])) {
    $success = sanitizeInput(urldecode($_GET['success']));
} elseif (isset($_GET['error'])) {
    $error = sanitizeInput(urldecode($_GET['error']));
}

// Fonction pour obtenir tous les commentaires (avec pagination si nécessaire)
function getAllCommentsAdmin($conn, $limit = 10, $offset = 0, $status = null) {
    $sql = "SELECT c.*, a.title AS article_title FROM comments c JOIN articles a ON c.article_id = a.id";
    $params = [];

    if ($status !== null) {
        $sql .= " WHERE c.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Fonction pour compter tous les commentaires
function countAllCommentsAdmin($conn, $status = null) {
    $sql = "SELECT COUNT(*) FROM comments";
    $params = [];
    if ($status !== null) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$comments_per_page = 10; // Nombre de commentaires par page
$offset = ($page - 1) * $comments_per_page;

// Filtrer par statut si nécessaire (par défaut, afficher tout)
$filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;

// Récupérer les commentaires pour la page actuelle avec filtre
$comments = getAllCommentsAdmin($conn, $comments_per_page, $offset, $filter_status);

// Compter le nombre total de commentaires pour la pagination avec filtre
$total_comments = countAllCommentsAdmin($conn, $filter_status);
$total_pages = ceil($total_comments / $comments_per_page);

include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestion des commentaires</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Commentaires</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Liste de tous les commentaires</h3>
                             <div class="card-tools">
                                <ul class="nav nav-pills ml-auto">
                                     <li class="nav-item">
                                        <a class="nav-link <?= $filter_status === null ? 'active' : '' ?>" href="comments.php">Tous</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $filter_status === 'pending' ? 'active' : '' ?>" href="comments.php?status=pending">En attente</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $filter_status === 'approved' ? 'active' : '' ?>" href="comments.php?status=approved">Approuvés</a>
                                    </li>
                                     <li class="nav-item">
                                        <a class="nav-link <?= $filter_status === 'rejected' ? 'active' : '' ?>" href="comments.php?status=rejected">Rejetés</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= $success ?></div>
                            <?php endif; ?>

                            <?php if (empty($comments)): ?>
                                <div class="alert alert-info">Aucun commentaire trouvé.</div>
                            <?php else: ?>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Auteur</th>
                                            <th>Email</th>
                                            <th>Commentaire</th>
                                            <th>Article</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($comments as $comment): ?>
                                            <tr>
                                                <td><?= $comment['id'] ?></td>
                                                <td><?= htmlspecialchars($comment['author_name'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($comment['author_email'] ?? '') ?></td>
                                                <td><?= htmlspecialchars(limitWords($comment['content'] ?? '', 20)) ?></td>
                                                <td>
                                                    <a href="../article.php?id=<?= $comment['article_id'] ?>" target="_blank">
                                                        <?= htmlspecialchars(limitWords($comment['article_title'], 10)) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusBadgeClass($comment['status']) ?>">
                                                        <?= ucfirst($comment['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= formatDate($comment['created_at']) ?></td>
                                                <td>
                                                    <?php if ($comment['status'] === 'pending'): ?>
                                                        <a href="comments.php?action=approve&id=<?= $comment['id'] ?>" class="btn btn-sm btn-success" title="Approuver">
                                                            <i class="bi bi-check"></i>
                                                        </a>
                                                        <a href="comments.php?action=reject&id=<?= $comment['id'] ?>" class="btn btn-sm btn-warning" title="Rejeter">
                                                            <i class="bi bi-x"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="comments.php?action=delete&id=<?= $comment['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?><?= $filter_status ? '&status=' . $filter_status : '' ?>">Précédent</a></li>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?><?= $filter_status ? '&status=' . $filter_status : '' ?>"><?= $i ?></a></li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?><?= $filter_status ? '&status=' . $filter_status : '' ?>">Suivant</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 