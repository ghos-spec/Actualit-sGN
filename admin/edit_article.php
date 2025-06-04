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
$articleData = null;
$article_id = (int)($_GET['id'] ?? 0);

// Récupérer l'article à modifier
if ($article_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$article_id]);
    $articleData = $stmt->fetch();

    if (!$articleData) {
        $_SESSION['error'] = "Article non trouvé.";
        header('Location: articles.php');
        exit;
    }
} else {
    $_SESSION['error'] = "ID d'article manquant.";
    header('Location: articles.php');
    exit;
}

// Récupérer les catégories et les journalistes pour les listes déroulantes
$categories_stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

$journalists_stmt = $conn->prepare("SELECT id, name FROM journalists ORDER BY name");
$journalists_stmt->execute();
$journalists = $journalists_stmt->fetchAll();

// Traiter le formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Summernote gère déjà le HTML
    $category_id = (int)($_POST['category_id'] ?? 0);
    $journalist_id = (int)($_POST['journalist_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'draft');
    $published_date = sanitizeInput($_POST['published_date'] ?? '');
    $current_image = sanitizeInput($_POST['current_image'] ?? '');
    $image_path = $current_image;

    // Validation simple
    if (empty($title) || empty($content) || $category_id === 0 || $journalist_id === 0) {
        $error = 'Le titre, le contenu, la catégorie et le journaliste sont obligatoires.';
    } else {
        try {
            // Gérer l'upload de la nouvelle image principale
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $upload_folder = 'uploads/articles';
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5242880; // 5MB

                $new_image_path = uploadFile($_FILES['main_image'], $upload_folder, $allowed_types, $max_size);
                
                if ($new_image_path) {
                    // Supprimer l'ancienne image si elle existe
                    if (!empty($current_image) && file_exists('../' . $current_image)) {
                        unlink('../' . $current_image);
                    }
                    $image_path = $new_image_path;
                } else {
                    throw new Exception('Erreur lors du téléversement de la nouvelle image principale.');
                }
            } elseif (isset($_POST['delete_current_image'])) {
                 // Supprimer l'image actuelle si la case est cochée
                 if (!empty($current_image) && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                    $image_path = null;
                 }
            }

            // Utiliser la date de publication si fournie, sinon NULL
            $published_date_sql = !empty($published_date) ? $published_date : null;

            // Mettre à jour l'article dans la base de données
            $stmt = $conn->prepare("UPDATE articles SET title = ?, content = ?, category_id = ?, journalist_id = ?, status = ?, published_date = ?, main_image = ? WHERE id = ?");
            $stmt->execute([$title, $content, $category_id, $journalist_id, $status, $published_date_sql, $image_path, $article_id]);

            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'edit_article', "Article ID: $article_id modifié");

            $_SESSION['success'] = 'Article mis à jour avec succès.';
            // Recharger les données de l'article après la mise à jour
             $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
             $stmt->execute([$article_id]);
             $articleData = $stmt->fetch();

        } catch (Exception $e) {
            $error = 'Une erreur est survenue : ' . $e->getMessage();
            logAdminAction($conn, $_SESSION['admin_id'], 'edit_article_error', "Erreur lors de la mise à jour de l'article ID $article_id : " . $e->getMessage());
        }
    }
}

include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Modifier l'Article</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="articles.php">Articles</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informations de l'Article</h3>
                </div>
                <div class="card-body">
                    <form action="edit_article.php?id=<?= $article_id ?>" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Titre *</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($articleData['title'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="content">Contenu *</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($articleData['content'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Catégorie *</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">-- Sélectionner une catégorie --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= ($articleData['category_id'] ?? 0) == $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="journalist_id">Journaliste *</label>
                            <select class="form-control" id="journalist_id" name="journalist_id" required>
                                <option value="">-- Sélectionner un journaliste --</option>
                                <?php foreach ($journalists as $journalist): ?>
                                    <option value="<?= $journalist['id'] ?>" <?= ($articleData['journalist_id'] ?? 0) == $journalist['id'] ? 'selected' : '' ?>><?= htmlspecialchars($journalist['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Statut</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft" <?= ($articleData['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Brouillon</option>
                                <option value="published" <?= ($articleData['status'] ?? '') == 'published' ? 'selected' : '' ?>>Publié</option>
                                <option value="archived" <?= ($articleData['status'] ?? '') == 'archived' ? 'selected' : '' ?>>Archivé</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="published_date">Date de publication (facultatif)</label>
                             <input type="datetime-local" class="form-control" id="published_date" name="published_date" value="<?= isset($articleData['published_date']) && $articleData['published_date'] ? date('Y-m-d\\TH:i', strtotime($articleData['published_date'])) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="main_image">Image principale (facultatif)</label><br>
                            <?php if (!empty($articleData['main_image'])): ?>
                                <img src="../<?= htmlspecialchars($articleData['main_image']) ?>" alt="Image actuelle" style="max-width: 200px; margin-bottom: 10px;"><br>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="delete_current_image" name="delete_current_image">
                                    <label class="form-check-label" for="delete_current_image">
                                        Supprimer l'image actuelle
                                    </label>
                                </div>
                                <input type="hidden" name="current_image" value="<?= htmlspecialchars($articleData['main_image']) ?>">
                            <?php endif; ?>
                            <label for="main_image">Choisir une nouvelle image</label>
                            <input type="file" class="form-control" id="main_image" name="main_image" accept="image/*">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            <a href="articles.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?>

<!-- Initialiser Summernote -->
<script>
    $(document).ready(function() {
        $('#content').summernote({
            height: 300,
            minHeight: null,
            maxHeight: null,
            focus: true
        });
    });
</script> 