<?php
/**
 * Gestion des Journalistes
 * 
 * Ce fichier gère l'affichage et la gestion des journalistes dans l'interface d'administration.
 * Seul le superadmin a accès à cette fonctionnalité.
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once 'includes/admin_functions.php';

// Vérification des permissions
// 1. Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Vérifier si l'utilisateur est connecté ET est superadmin
// Si l'une de ces conditions n'est pas remplie, rediriger vers la page d'accueil
if (!isLoggedIn() || !isSuperAdmin($conn)) {
    $_SESSION['error'] = "Accès non autorisé. Seul le superadmin peut gérer les journalistes.";
    header('Location: index.php');
    exit;
}

// Traiter la recherche
// Permet de filtrer les journalistes par nom, email ou titre
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE j.name LIKE ? OR j.email LIKE ? OR j.title LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// Récupérer tous les journalistes avec leurs statistiques
// Compte le nombre total d'articles et d'articles publiés pour chaque journaliste
$sql = "SELECT j.*, 
        COUNT(DISTINCT a.id) as total_articles,
        COUNT(DISTINCT CASE WHEN a.status = 'published' THEN a.id END) as published_articles
        FROM journalists j
        LEFT JOIN articles a ON j.id = a.journalist_id
        $where_clause
        GROUP BY j.id
        ORDER BY j.name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$journalists = $stmt->fetchAll();

// Inclure l'en-tête de l'interface d'administration
include 'includes/admin_header.php';
?>

<!-- Interface utilisateur -->
<div class="content-wrapper">
    <!-- En-tête de la page -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestion des Journalistes</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Journalistes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <section class="content">
        <div class="container-fluid">
            <!-- Messages de notification -->
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

            <!-- Carte principale -->
            <div class="card">
                <!-- En-tête de la carte -->
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Liste des Journalistes</h3>
                        <!-- Bouton d'ajout de journaliste -->
                        <a href="add_journalist.php" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Ajouter un journaliste
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Formulaire de recherche -->
                    <form method="get" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Rechercher un journaliste..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="journalists.php" class="btn btn-secondary">
                                    <i class="bi bi-x"></i> Effacer
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Tableau des journalistes -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Titre</th>
                                    <th>Articles</th>
                                    <th>Publiés</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($journalists)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucun journaliste trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($journalists as $journalist): ?>
                                        <tr>
                                            <!-- Photo de profil -->
                                            <td>
                                                <?php if ($journalist['avatar']): ?>
                                                    <img src="../<?= htmlspecialchars($journalist['avatar']) ?>" alt="Avatar" class="img-thumbnail" style="max-width: 50px;">
                                                <?php else: ?>
                                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($journalist['name']) ?></td>
                                            <td><?= htmlspecialchars($journalist['email']) ?></td>
                                            <td><?= htmlspecialchars($journalist['title'] ?? '') ?></td>
                                            <td><?= $journalist['total_articles'] ?></td>
                                            <td><?= $journalist['published_articles'] ?></td>
                                            <!-- Actions disponibles -->
                                            <td>
                                                <div class="btn-group">
                                                    <!-- Bouton de modification -->
                                                    <a href="edit_journalist.php?id=<?= $journalist['id'] ?>" class="btn btn-sm btn-info" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <!-- Bouton de suppression (uniquement si pas d'articles) -->
                                                    <?php if ($journalist['total_articles'] == 0): ?>
                                                        <a href="delete_journalist.php?id=<?= $journalist['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce journaliste ?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 