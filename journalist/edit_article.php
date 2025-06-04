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

// Vérifier l'ID de l'article
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$article_id) {
    header('Location: articles.php');
    exit;
}

// Récupérer l'article
$stmt = $conn->prepare("
    SELECT * FROM articles 
    WHERE id = ? AND journalist_id = ?
");
$stmt->execute([$article_id, $_SESSION['journalist_id']]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: articles.php');
    exit;
}

// Récupérer les catégories
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

$error = '';
$success = '';

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = sanitizeInput($_POST['excerpt'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'draft');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_breaking = isset($_POST['is_breaking']) ? 1 : 0;
    
    // Validation
    if (empty($title) || empty($content) || empty($category_id)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        // Générer le slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        // Gérer l'upload d'image
        $image_path = $article['image_path'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/articles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne image si elle existe
                    if ($article['image_path'] && file_exists('../' . $article['image_path'])) {
                        unlink('../' . $article['image_path']);
                    }
                    $image_path = 'uploads/articles/' . $new_filename;
                }
            }
        }
        
        // Gérer l'upload de vidéo
        $video_path = $article['video_path'] ?? null;
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/videos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
            if ($file_extension === 'mp4') {
                $new_filename = uniqid() . '.mp4';
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['video']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne vidéo si elle existe
                    if (!empty($article['video_path']) && file_exists('../' . $article['video_path'])) {
                        unlink('../' . $article['video_path']);
                    }
                    $video_path = 'uploads/videos/' . $new_filename;
                }
            }
        }
        
        try {
            // Mettre à jour l'article
            $stmt = $conn->prepare("
                UPDATE articles SET
                    title = ?, slug = ?, content = ?, excerpt = ?, image_path = ?,
                    video_url = ?,
                    category_id = ?, status = ?, is_featured = ?, is_breaking = ?,
                    updated_at = NOW()
                WHERE id = ? AND journalist_id = ?
            ");
            
            $stmt->execute([
                $title, $slug, $content, $excerpt, $image_path,
                $video_path,
                $category_id, $status, $is_featured, $is_breaking,
                $article_id, $_SESSION['journalist_id']
            ]);
            
            $success = 'Article mis à jour avec succès.';
            
            // Rediriger vers la liste des articles
            header('Location: articles.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Une erreur est survenue lors de la mise à jour de l\'article.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'article - Espace Journaliste</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/me9qn87529iyoswo9hbchk1h37y616vtycc3u99fhrhatd86/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 500,
            language: 'fr_FR',
            branding: false,
            promotion: false,
            images_upload_url: 'upload_image.php',
            automatic_uploads: true,
            file_picker_types: 'image',
            images_reuse_filename: true,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true
        });
    </script>
    
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
                        <a class="nav-link" href="articles.php">Mes articles</a>
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
            <h1>Modifier l'article</h1>
            <a href="articles.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($article['title']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Contenu <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="content" name="content" required><?= htmlspecialchars($article['content']) ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="excerpt" class="form-label">Extrait</label>
                                <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= htmlspecialchars($article['excerpt']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Paramètres</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Sélectionner une catégorie</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>" <?= $category['id'] === $article['category_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Statut</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                                            <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Publié</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Image principale</label>
                                        <?php if ($article['image_path']): ?>
                                            <div class="mb-2">
                                                <img src="../<?= htmlspecialchars($article['image_path']) ?>" alt="Image actuelle" class="img-thumbnail" style="max-height: 200px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="video" class="form-label">Vidéo (MP4)</label>
                                        <?php if (!empty($article['video_path'])): ?>
                                            <div class="mb-2">
                                                <video controls style="max-width: 100%;">
                                                    <source src="../<?= htmlspecialchars($article['video_path']) ?>" type="video/mp4">
                                                    Votre navigateur ne supporte pas la lecture de vidéos.
                                                </video>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="video" name="video" accept="video/mp4">
                                        <small class="form-text text-muted">Taille maximale : 10MB</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" <?= $article['is_featured'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_featured">Article à la une</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_breaking" name="is_breaking" <?= $article['is_breaking'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_breaking">Breaking news</label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Mettre à jour l'article</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 