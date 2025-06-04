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
$categoryData = null;
$category_id = (int)($_GET['id'] ?? 0);

// Récupérer la catégorie à modifier
if ($category_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $categoryData = $stmt->fetch();

    if (!$categoryData) {
        $_SESSION['error'] = "Catégorie non trouvée.";
        header('Location: categories.php');
        exit;
    }
} else {
    $_SESSION['error'] = "ID de catégorie manquant.";
    header('Location: categories.php');
    exit;
}

// Traiter le formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validation simple
    if (empty($name)) {
        $error = 'Le nom de la catégorie est obligatoire.';
    } else {
        try {
            // Créer le slug (basé sur le nouveau nom)
            $slug = createSlug($name);
            
            // Vérifier si le slug existe déjà pour une autre catégorie
            $stmt_check_slug = $conn->prepare("SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?");
            $stmt_check_slug->execute([$slug, $category_id]);
            if ($stmt_check_slug->fetchColumn() > 0) {
                throw new Exception('Ce nom de catégorie génère un slug déjà utilisé. Veuillez choisir un autre nom.');
            }

            // Mettre à jour la catégorie dans la base de données
            $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $description, $category_id]);

            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'edit_category', "Catégorie ID: $category_id modifiée");

            $_SESSION['success'] = 'Catégorie mise à jour avec succès.';
            // Recharger les données de la catégorie après la mise à jour
             $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
             $stmt->execute([$category_id]);
             $categoryData = $stmt->fetch();

        } catch (Exception $e) {
            $error = 'Une erreur est survenue : ' . $e->getMessage();
            logAdminAction($conn, $_SESSION['admin_id'], 'edit_category_error', "Erreur lors de la mise à jour de la catégorie ID $category_id : " . $e->getMessage());
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
                    <h1 class="m-0">Modifier la Catégorie</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="categories.php">Catégories</a></li>
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
                    <h3 class="card-title">Informations de la Catégorie</h3>
                </div>
                <div class="card-body">
                    <form action="edit_category.php?id=<?= $category_id ?>" method="post">
                        <div class="form-group">
                            <label for="name">Nom de la catégorie *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($categoryData['name'] ?? '') ?>" required>
                        </div>

                         <div class="form-group">
                            <label for="description">Description (facultatif)</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($categoryData['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                             <a href="categories.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 