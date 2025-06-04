<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Vérifier si connecté et si l'utilisateur est un journaliste
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['journalist_id']) || strtolower($_SESSION['journalist_role']) !== 'journaliste') {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté ou n'est pas un journaliste
    header('Location: login.php');
    exit;
}

$journalistId = (int)$_SESSION['journalist_id'];

// Récupérer les informations du journaliste
$stmt = $conn->prepare("SELECT * FROM journalists WHERE id = ?");
$stmt->execute([$journalistId]);
$journalist = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le journaliste n'est pas trouvé (ce qui ne devrait pas arriver si la session est valide, mais par sécurité)
if (!$journalist) {
    // Détruire la session et rediriger vers la connexion
    session_destroy();
    header('Location: login.php');
    exit;
}

// --- Récupérer les métriques et articles du journaliste ---

// Compter le nombre total d'articles
$stmtTotalArticles = $conn->prepare("SELECT COUNT(*) FROM articles WHERE journalist_id = ?");
$stmtTotalArticles->execute([$journalistId]);
$totalArticles = $stmtTotalArticles->fetchColumn();

// Compter le nombre d'articles publiés
$stmtPublishedArticles = $conn->prepare("SELECT COUNT(*) FROM articles WHERE journalist_id = ? AND status = 'published'");
$stmtPublishedArticles->execute([$journalistId]);
$publishedArticles = $stmtPublishedArticles->fetchColumn();

// Compter le nombre total de vues sur les articles publiés du journaliste
$stmtTotalViews = $conn->prepare("SELECT SUM(views) FROM articles WHERE journalist_id = ? AND status = 'published'");
$stmtTotalViews->execute([$journalistId]);
$totalViews = $stmtTotalViews->fetchColumn() ?? 0; // Utilise ?? 0 pour gérer le cas où SUM retourne NULL

// Récupérer les articles récents du journaliste (par exemple, les 5 derniers)
$stmtRecentArticles = $conn->prepare("
    SELECT a.id, a.title, a.status, a.created_at, a.published_date, a.views,
           c.name as category_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.journalist_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmtRecentArticles->execute([$journalistId]);
$recentArticles = $stmtRecentArticles->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Mon Profil - " . htmlspecialchars($journalist['name']);

include '../includes/journalist_header.php'; // Assurez-vous d'avoir un header spécifique pour les journalistes

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Profil de <?= htmlspecialchars($journalist['name']) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Profil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <!-- Profile Image Card -->
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <img class="profile-user-img img-fluid img-circle" src="<?= !empty($journalist['avatar']) ? '../' . htmlspecialchars($journalist['avatar']) : '../assets/img/default-avatar.png' ?>" alt="User profile picture">
                            </div>

                            <h3 class="profile-username text-center"><?= htmlspecialchars($journalist['name']) ?></h3>
                            <p class="text-muted text-center"><?= htmlspecialchars($journalist['title'] ?? 'Journaliste') ?></p>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Total Articles</b> <a class="float-right"><?= $totalArticles ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Articles Publiés</b> <a class="float-right"><?= $publishedArticles ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Vues Totales (Publiés)</b> <a class="float-right"><?= $totalViews ?></a>
                                </li>
                            </ul>

                            <!-- Bouton d'édition du profil si nécessaire -->
                            <!-- <a href="edit_profile.php" class="btn btn-primary btn-block"><b>Modifier le Profil</b></a> -->
                        </div>
                    </div>

                    <!-- About Me Card -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">À Propos</h3>
                        </div>
                        <div class="card-body">
                            <strong><i class="bi bi-envelope me-1"></i> Email</strong>
                            <p class="text-muted"><?= htmlspecialchars($journalist['email']) ?></p>
                            <hr>

                            <strong><i class="bi bi-file-text me-1"></i> Biographie</strong>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($journalist['bio'] ?? 'Pas de biographie disponible.')) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Articles List Column -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Mes Articles Récents (Derniers 5)</h3>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentArticles)): ?>
                                <p class="text-muted text-center mt-3">Aucun article trouvé.</p>
                            <?php else: ?>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Titre</th>
                                            <th>Statut</th>
                                            <th>Vues</th>
                                            <th>Date</th>
                                            <th style="width: 40px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentArticles as $article): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($article['title']) ?></td>
                                            <td><span class="badge bg-<?= $article['status'] === 'published' ? 'success' : 'warning' ?>"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span></td>
                                            <td><?= $article['views'] ?></td>
                                            <td><?= formatDate($article['created_at']) ?></td>
                                            <td>
                                                <a href="edit_article.php?id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                         <div class="card-footer text-center">
                            <a href="articles.php" class="btn btn-sm btn-secondary">Voir tous mes articles</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../includes/journalist_footer.php'; // Assurez-vous d'avoir un footer spécifique pour les journalistes ?> 