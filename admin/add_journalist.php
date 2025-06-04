<?php
/**
 * Ajout d'un Journaliste
 * 
 * Ce fichier gère l'ajout de nouveaux journalistes dans l'interface d'administration.
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
    $_SESSION['error'] = "Accès non autorisé. Seul le superadmin peut ajouter des journalistes.";
    header('Location: index.php');
    exit;
}

// Traiter le formulaire d'ajout
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

        // 2. Vérification de l'unicité de l'email
        $stmt = $conn->prepare("SELECT id FROM journalists WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Cette adresse email est déjà utilisée.");
        }

        // 3. Gestion de l'upload de l'avatar
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatar = uploadFile($_FILES['avatar'], 'uploads/avatars');
            if (!$avatar) {
                throw new Exception("Erreur lors de l'upload de l'avatar.");
            }
        }

        // Générer un mot de passe par défaut
        $default_password = bin2hex(random_bytes(4)); // Génère un mot de passe aléatoire

        // 4. Insertion du journaliste dans la base de données
        $stmt = $conn->prepare("
            INSERT INTO journalists (name, email, title, bio, avatar, role, password)
            VALUES (?, ?, ?, ?, ?, 'journaliste', ?)
        ");
        $stmt->execute([$name, $email, $title, $bio, $avatar, $default_password]);

        // 5. Journalisation de l'action
        logAdminAction($conn, $_SESSION['admin_id'], 'add_journalist', "Nouveau journaliste ajouté : $name");

        // 6. Redirection avec message de succès incluant le mot de passe
        $_SESSION['success'] = "Le journaliste a été ajouté avec succès. Mot de passe : $default_password";
        header('Location: journalists.php');
        exit;
    } catch (Exception $e) {
        // En cas d'erreur, afficher le message
        $_SESSION['error'] = "Erreur lors de l'ajout du journaliste : " . $e->getMessage();
    }
}

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
                    <h1 class="m-0">Ajouter un Journaliste</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="journalists.php">Journalistes</a></li>
                        <li class="breadcrumb-item active">Ajouter</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <section class="content">
        <div class="container-fluid">
            <!-- Messages d'erreur -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Formulaire d'ajout -->
            <div class="card">
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <!-- Champs du formulaire -->
                        <div class="form-group">
                            <label for="name">Nom *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="title">Titre</label>
                            <input type="text" class="form-control" id="title" name="title">
                        </div>

                        <div class="form-group">
                            <label for="bio">Biographie</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="avatar">Photo de profil</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            <small class="form-text text-muted">Formats acceptés : JPG, PNG. Taille maximale : 5MB</small>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Ajouter le journaliste</button>
                            <a href="journalists.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 