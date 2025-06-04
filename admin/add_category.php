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

// Traiter le formulaire d'ajout de catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validation simple
    if (empty($name)) {
        $error = 'Le nom de la catégorie est obligatoire.';
    } else {
        try {
            // Créer le slug
            $slug = createSlug($name);
            
            // Vérifier si le slug existe déjà
            $stmt_check_slug = $conn->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
            $stmt_check_slug->execute([$slug]);
            if ($stmt_check_slug->fetchColumn() > 0) {
                throw new Exception('Ce nom de catégorie génère un slug déjà utilisé. Veuillez choisir un autre nom.');
            }

            // Insérer la catégorie dans la base de données
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $slug, $description]);

            $category_id = $conn->lastInsertId();

            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'add_category', "Catégorie ID: $category_id ajoutée");

            $_SESSION['success'] = 'Catégorie ajoutée avec succès.';
            header('Location: categories.php');
            exit;

        } catch (Exception $e) {
            $error = 'Une erreur est survenue : ' . $e->getMessage();
            logAdminAction($conn, $_SESSION['admin_id'], 'add_category_error', 'Erreur lors de l\'ajout de la catégorie : ' . $e->getMessage());
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
                    <h1 class="m-0">Ajouter une Nouvelle Catégorie</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="categories.php">Catégories</a></li>
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
                    <h3 class="card-title">Informations de la Catégorie</h3>
                </div>
                <div class="card-body">
                    <form action="add_category.php" method="post">
                        <div class="form-group">
                            <label for="name">Nom de la catégorie *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>

                         <div class="form-group">
                            <label for="description">Description (facultatif)</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Ajouter la catégorie</button>
                             <a href="categories.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 