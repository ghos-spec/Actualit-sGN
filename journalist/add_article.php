<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Récupérer les catégories pour le formulaire
$stmtCategories = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmtCategories->execute();
$categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Traiter le formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Le contenu HTML peut être autorisé
    $excerpt = sanitizeInput($_POST['excerpt'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'draft'); // Statut par défaut : brouillon
    $published_date = ($status === 'published') ? date('Y-m-d H:i:s') : null; // Date de publication si publié immédiatement
    $image_path = null;
    $video_url = sanitizeInput($_POST['video_url'] ?? '');
    $video_path = null;

    // Validation simple
    if (empty($title) || empty($content) || empty($category_id)) {
        $error = 'Veuillez remplir le titre, le contenu et sélectionner une catégorie.';
    } else {
        try {
            // Gérer l'upload de l'image principale
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $upload_folder = 'uploads/articles';
                // Ajuster les types autorisés si nécessaire
                $allowed_types_image = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; 
                $max_size_image = 5242880; // 5MB

                $image_path = uploadFile($_FILES['main_image'], $upload_folder, $allowed_types_image, $max_size_image);

                if (!$image_path) {
                    throw new Exception('Erreur lors du téléversement de l\'image principale. Vérifiez la taille (max 5MB) ou le type de fichier (JPG, PNG, GIF, WebP).');
                }
            }

            // Gérer l'upload de la vidéo
            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $upload_folder = 'uploads/videos';
                $allowed_types_video = ['video/mp4', 'video/webm', 'video/ogg'];
                $max_size_video = 104857600; // 100MB

                $video_path = uploadFile($_FILES['video_file'], $upload_folder, $allowed_types_video, $max_size_video);

                if (!$video_path) {
                    throw new Exception('Erreur lors du téléversement de la vidéo. Vérifiez la taille (max 100MB) ou le type de fichier (MP4, WebM, OGG).');
                }
            }

            // Insérer l'article dans la base de données
            $stmtInsert = $conn->prepare("
                INSERT INTO articles (
                    title, 
                    content, 
                    excerpt, 
                    category_id, 
                    journalist_id, 
                    status, 
                    published_date, 
                    image_path,
                    video_url,
                    video_path,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmtInsert->execute([
                $title,
                $content,
                $excerpt,
                $category_id,
                $journalistId,
                $status,
                $published_date,
                $image_path,
                $video_url,
                $video_path
            ]);

            $articleId = $conn->lastInsertId();

            // Rediriger vers la page d'édition ou de liste des articles après succès
            $_SESSION['flash_message'] = 'Nouvel article créé avec succès.';
            $_SESSION['flash_type'] = 'success';
            header('Location: articles.php');
            exit;

        } catch (Exception $e) {
            $error = 'Une erreur est survenue lors de l\'ajout de l\'article : ' . $e->getMessage();
            // Supprimer les fichiers uploadés en cas d'erreur
            if ($image_path && file_exists('../' . $image_path)) {
                unlink('../' . $image_path);
            }
            if ($video_path && file_exists('../' . $video_path)) {
                unlink('../' . $video_path);
            }
            // Enregistrer l'erreur dans les logs du serveur
            error_log("Journalist add article error: " . $e->getMessage());
        }
    }
}

$pageTitle = "Ajouter un Nouvel Article";

include '../includes/journalist_header.php'; // Assurez-vous d'avoir un header spécifique pour les journalistes

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
                        <li class="breadcrumb-item"><a href="articles.php">Mes Articles</a></li>
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
                            <label for="excerpt">Extrait (Optionnel)</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Un court résumé de l'article.</small>
                        </div>

                        <div class="form-group">
                            <label for="content">Contenu *</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                             <small class="form-text text-muted">Vous pouvez utiliser des balises HTML simples pour la mise en forme.</small>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Catégorie *</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">-- Sélectionner une catégorie --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= (($_POST['category_id'] ?? 0) == $category['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                         <div class="form-group">
                            <label for="main_image">Image Principale (Optionnel)</label>
                            <input type="file" class="form-control" id="main_image" name="main_image" accept="image/*">
                            <small class="form-text text-muted">Formats acceptés : JPG, PNG, GIF, WebP. Taille maximale : 5MB.</small>
                        </div>

                        <div class="form-group">
                            <label>Média Vidéo (Optionnel)</label>
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="video_url">URL de la vidéo</label>
                                        <input type="url" class="form-control" id="video_url" name="video_url" value="<?= htmlspecialchars($_POST['video_url'] ?? '') ?>" placeholder="Ex: https://www.youtube.com/watch?v=VIDEO_ID">
                                        <small class="form-text text-muted">Collez l'URL d'une vidéo YouTube, Vimeo, ou autre plateforme de streaming.</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="video_file">Ou téléversez une vidéo</label>
                                        <input type="file" class="form-control" id="video_file" name="video_file" accept="video/*">
                                        <small class="form-text text-muted">Formats acceptés : MP4, WebM, OGG. Taille maximale : 100MB.</small>
                                    </div>

                                    <div id="video_preview" class="mt-3" style="display: none;">
                                        <h6>Aperçu de la vidéo :</h6>
                                        <div class="ratio ratio-16x9">
                                            <iframe id="video_frame" src="" allowfullscreen></iframe>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <div class="form-group">
                            <label for="status">Statut</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft" <?= (($_POST['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Brouillon</option>
                                <option value="published" <?= (($_POST['status'] ?? 'draft') === 'published') ? 'selected' : '' ?>>Publier</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Créer l'Article</button>
                            <a href="articles.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoUrlInput = document.getElementById('video_url');
    const videoPreview = document.getElementById('video_preview');
    const videoFrame = document.getElementById('video_frame');

    function updateVideoPreview() {
        const url = videoUrlInput.value.trim();
        if (!url) {
            videoPreview.style.display = 'none';
            return;
        }

        // Convertir l'URL YouTube en URL d'intégration
        let embedUrl = url;
        if (url.includes('youtube.com/watch')) {
            const videoId = new URL(url).searchParams.get('v');
            if (videoId) {
                embedUrl = `https://www.youtube.com/embed/${videoId}`;
            }
        } else if (url.includes('youtu.be/')) {
            const videoId = url.split('youtu.be/')[1];
            if (videoId) {
                embedUrl = `https://www.youtube.com/embed/${videoId}`;
            }
        } else if (url.includes('vimeo.com/')) {
            const videoId = url.split('vimeo.com/')[1];
            if (videoId) {
                embedUrl = `https://player.vimeo.com/video/${videoId}`;
            }
        }

        videoFrame.src = embedUrl;
        videoPreview.style.display = 'block';
    }

    videoUrlInput.addEventListener('input', updateVideoPreview);
    videoUrlInput.addEventListener('change', updateVideoPreview);

    // Mettre à jour la prévisualisation au chargement si une URL est déjà présente
    if (videoUrlInput.value) {
        updateVideoPreview();
    }
});
</script>

<?php include '../includes/journalist_footer.php'; ?> 