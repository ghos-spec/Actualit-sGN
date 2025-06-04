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

// Récupérer les catégories et les journalistes pour les listes déroulantes
$categories_stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

$journalists_stmt = $conn->prepare("SELECT id, name FROM journalists ORDER BY name");
$journalists_stmt->execute();
$journalists = $journalists_stmt->fetchAll();

// Traiter le formulaire d'ajout d'article
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Summernote gère déjà le HTML
    $category_id = (int)($_POST['category_id'] ?? 0);
    $journalist_id = (int)($_POST['journalist_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'draft');
    $published_date = sanitizeInput($_POST['published_date'] ?? '');
    $image_path = null;

    // Validation simple
    if (empty($title) || empty($content) || $category_id === 0 || $journalist_id === 0) {
        $error = 'Le titre, le contenu, la catégorie et le journaliste sont obligatoires.';
    } else {
        try {
            // Gérer l'upload de l'image principale
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $upload_folder = 'uploads/articles';
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5242880; // 5MB

                $image_path = uploadFile($_FILES['main_image'], $upload_folder, $allowed_types, $max_size);
                
                if (!$image_path) {
                    throw new Exception('Erreur lors du téléversement de l\'image principale.');
                }
            }

            // Utiliser la date de publication si fournie, sinon NULL
            $published_date_sql = !empty($published_date) ? $published_date : null;

            // Insérer l'article dans la base de données
            $stmt = $conn->prepare("INSERT INTO articles (title, content, category_id, journalist_id, status, published_date, main_image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $content, $category_id, $journalist_id, $status, $published_date_sql, $image_path]);

            $article_id = $conn->lastInsertId();

            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'add_article', "Article ID: $article_id ajouté");

            $_SESSION['success'] = 'Article ajouté avec succès.';
            header('Location: articles.php');
            exit;

        } catch (Exception $e) {
            $error = 'Une erreur est survenue : ' . $e->getMessage();
            // Supprimer le fichier uploadé en cas d'erreur base de données
            if ($image_path && file_exists('../' . $image_path)) {
                unlink('../' . $image_path);
            }
            logAdminAction($conn, $_SESSION['admin_id'], 'add_article_error', 'Erreur lors de l\'ajout de l\'article : ' . $e->getMessage());
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
                    <h1 class="m-0">Ajouter un Nouvel Article</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="articles.php">Articles</a></li>
                        <li class="breadcrumb-item active">Ajouter</li>
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
                    <form action="add_article.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Titre *</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="content">Contenu *</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Catégorie *</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">-- Sélectionner une catégorie --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="journalist_id">Journaliste *</label>
                            <select class="form-control" id="journalist_id" name="journalist_id" required>
                                <option value="">-- Sélectionner un journaliste --</option>
                                <?php foreach ($journalists as $journalist): ?>
                                    <option value="<?= $journalist['id'] ?>" <?= (isset($_POST['journalist_id']) && $_POST['journalist_id'] == $journalist['id']) ? 'selected' : '' ?>><?= htmlspecialchars($journalist['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Statut</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft" <?= (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : '' ?>>Brouillon</option>
                                <option value="published" <?= (isset($_POST['status']) && $_POST['status'] == 'published') ? 'selected' : '' ?>>Publié</option>
                                <option value="archived" <?= (isset($_POST['status']) && $_POST['status'] == 'archived') ? 'selected' : '' ?>>Archivé</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="published_date">Date de publication (facultatif)</label>
                            <input type="datetime-local" class="form-control" id="published_date" name="published_date" value="<?= htmlspecialchars($_POST['published_date'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="main_image">Image principale (facultatif)</label>
                            <input type="file" class="form-control" id="main_image" name="main_image" accept="image/*">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Ajouter l'article</button>
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