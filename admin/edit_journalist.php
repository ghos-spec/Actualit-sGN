<?php
/**
 * Modification d'un Journaliste
 * 
 * Ce fichier gère la modification des informations d'un journaliste existant.
 * Seul le superadmin a accès à cette fonctionnalité.
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once './includes/admin_functions.php';

// Vérification des permissions
// 1. Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Vérifier si l'utilisateur est connecté ET est superadmin
// Si l'une de ces conditions n'est pas remplie, rediriger vers la page d'accueil
if (!isLoggedIn() || !isSuperAdmin($conn)) {
    $_SESSION['error'] = "Accès non autorisé. Seul le superadmin peut modifier les journalistes.";
    header('Location: index.php');
    exit;
}

// Vérification de l'ID du journaliste
// 1. Vérifier si l'ID est fourni dans l'URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID du journaliste non spécifié.";
    header('Location: journalists.php');
    exit;
}

$journalist_id = (int)$_GET['id'];

// 2. Récupérer les informations du journaliste
$stmt = $conn->prepare("SELECT * FROM journalists WHERE id = ?");
$stmt->execute([$journalist_id]);
$journalist = $stmt->fetch();

// 3. Vérifier si le journaliste existe
if (!$journalist) {
    $_SESSION['error'] = "Journaliste non trouvé.";
    header('Location: journalists.php');
    exit;
}

// Traiter le formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Validation des données
        // Récupérer et nettoyer les données du formulaire
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $title = trim($_POST['title']);
        $bio = trim($_POST['bio']);

        // Vérifier les champs obligatoires
        if (empty($name) || empty($email)) {
            throw new Exception("Le nom et l'email sont obligatoires.");
        }

        // Valider le format de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("L'adresse email n'est pas valide.");
        }

        // 2. Vérification de l'unicité de l'email (sauf pour ce journaliste)
        $stmt = $conn->prepare("SELECT id FROM journalists WHERE email = ? AND id != ?");
        $stmt->execute([$email, $journalist_id]);
        if ($stmt->fetch()) {
            throw new Exception("Cette adresse email est déjà utilisée.");
        }

        // 3. Gestion de l'upload de l'avatar
        $avatar = $journalist['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $new_avatar = uploadFile($_FILES['avatar'], 'uploads/avatars');
            if (!$new_avatar) {
                throw new Exception("Erreur lors de l'upload de l'avatar.");
                }
            
            // Supprimer l'ancien avatar s'il existe
            if ($avatar && file_exists('../' . $avatar)) {
                unlink('../' . $avatar);
            }
            
            $avatar = $new_avatar;
        }

        // 4. Mise à jour du journaliste dans la base de données
        $stmt = $conn->prepare("
            UPDATE journalists 
            SET name = ?, email = ?, title = ?, bio = ?, avatar = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $email, $title, $bio, $avatar, $journalist_id]);

        // 5. Journalisation de l'action
        logAdminAction($conn, $_SESSION['admin_id'], 'edit_journalist', "Journaliste modifié : $name");

        // 6. Redirection avec message de succès
        $_SESSION['success'] = "Le journaliste a été modifié avec succès.";
        header('Location: journalists.php');
        exit;
    } catch (Exception $e) {
        // En cas d'erreur, afficher le message
        $_SESSION['error'] = "Erreur lors de la modification du journaliste : " . $e->getMessage();
            }
        }

include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Modifier le Journaliste</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="journalists.php">Journalistes</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
    <div class="container-fluid">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Nom *</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($journalist['name']) ?>" required>
                            </div>
                            
                        <div class="form-group">
                            <label for="email">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($journalist['email']) ?>" required>
                            </div>

                        <div class="form-group">
                            <label for="title">Titre</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($journalist['title'] ?? '') ?>">
                            </div>

                        <div class="form-group">
                            <label for="bio">Biographie</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($journalist['bio'] ?? '') ?></textarea>
                            </div>
                            
                        <div class="form-group">
                            <label for="avatar">Photo de profil</label>
                                <?php if ($journalist['avatar']): ?>
                                    <div class="mb-2">
                                    <img src="../<?= htmlspecialchars($journalist['avatar']) ?>" alt="Avatar actuel" class="img-thumbnail" style="max-width: 100px;">
                                    </div>
                                <?php endif; ?>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            <small class="form-text text-muted">Formats acceptés : JPG, PNG. Taille maximale : 5MB</small>
                            </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            <a href="journalists.php" class="btn btn-secondary">Annuler</a>
                        </div>
                        </form>
                    </div>
                </div>
        </div>
    </section>
    </div>

<?php include 'includes/admin_footer.php'; ?> 