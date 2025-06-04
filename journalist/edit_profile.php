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

// Récupérer les informations actuelles du journaliste
$stmt = $conn->prepare("SELECT * FROM journalists WHERE id = ?");
$stmt->execute([$journalistId]);
$journalist = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le journaliste n'est pas trouvé (ne devrait pas arriver)
if (!$journalist) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Traiter le formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $title = sanitizeInput($_POST['title'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    $current_avatar = sanitizeInput($_POST['current_avatar'] ?? '');
    $avatar_path = $current_avatar; // Par défaut, on garde l'avatar actuel

    // Validation simple
    if (empty($name) || empty($email)) {
        $error = 'Le nom et l\'email sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Le format de l\'email est invalide.';
    } else {
        try {
            // Vérifier si l'email existe déjà pour un autre journaliste
            $stmtCheckEmail = $conn->prepare("SELECT COUNT(*) FROM journalists WHERE email = ? AND id != ?");
            $stmtCheckEmail->execute([$email, $journalistId]);
            if ($stmtCheckEmail->fetchColumn() > 0) {
                throw new Exception('Cet email est déjà utilisé par un autre compte.');
            }

            // Gérer l'upload de l'avatar
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_folder = 'uploads/avatars'; // Dossier d'upload pour les avatars
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif']; // Types d'images autorisés
                $max_size = 2097152; // 2MB en octets

                // Utiliser la fonction uploadFile pour gérer l'upload
                // Assurez-vous que uploadFile est disponible et fonctionne comme prévu
                $new_avatar_path = uploadFile($_FILES['avatar'], $upload_folder, $allowed_types, $max_size);

                if ($new_avatar_path) {
                    // Supprimer l'ancien avatar s'il existe et n'est pas l'avatar par défaut
                    if (!empty($current_avatar) && file_exists('../' . $current_avatar) && strpos($current_avatar, 'default-avatar.png') === false) {
                        unlink('../' . $current_avatar);
                    }
                    $avatar_path = $new_avatar_path;
                } else {
                    // Gérer l'erreur d'upload de fichier
                    throw new Exception('Erreur lors du téléversement de l\'avatar. Vérifiez la taille (max 2MB) ou le type de fichier (JPG, PNG, GIF).');
                }
            } elseif (isset($_POST['delete_current_avatar']) && !empty($current_avatar) && strpos($current_avatar, 'default-avatar.png') === false) {
                // Supprimer l'avatar actuel si la case est cochée et ce n'est pas l'avatar par défaut
                if (file_exists('../' . $current_avatar)) {
                    unlink('../' . $current_avatar);
                }
                $avatar_path = null; // Définir l'avatar sur NULL dans la base de données
            }

            // Mettre à jour les informations du journaliste dans la base de données
            $stmtUpdate = $conn->prepare("UPDATE journalists SET name = ?, email = ?, title = ?, bio = ?, avatar = ? WHERE id = ?");
            $stmtUpdate->execute([$name, $email, $title, $bio, $avatar_path, $journalistId]);

            $success = 'Profil mis à jour avec succès.';
            // Recharger les informations du journaliste après la mise à jour pour afficher les nouvelles données
            $stmt = $conn->prepare("SELECT * FROM journalists WHERE id = ?");
            $stmt->execute([$journalistId]);
            $journalist = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $error = 'Une erreur est survenue : ' . $e->getMessage();
            // Vous pourriez vouloir logger l'erreur ici aussi
            // error_log("Journalist profile update error: " . $e->getMessage());
        }
    }
}

$pageTitle = "Modifier mon Profil - " . htmlspecialchars($journalist['name']);

include '../includes/journalist_header.php'; // Assurez-vous d'avoir un header spécifique pour les journalistes

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Modifier mon Profil</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="profile.php">Profil</a></li>
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
                    <h3 class="card-title">Informations du Profil</h3>
                </div>
                <div class="card-body">
                    <form action="edit_profile.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="current_avatar" value="<?= htmlspecialchars($journalist['avatar'] ?? '') ?>">

                        <div class="form-group">
                            <label for="name">Nom *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($journalist['name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($journalist['email'] ?? '') ?>" required>
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
                            <?php if (!empty($journalist['avatar'])): ?>
                                <div class="mb-2">
                                    <img src="<?= '../' . htmlspecialchars($journalist['avatar']) ?>" alt="Avatar actuel" class="img-thumbnail" style="max-width: 150px;">
                                </div>
                                <?php if (strpos($journalist['avatar'], 'default-avatar.png') === false): // Permettre la suppression si ce n'est pas l'avatar par défaut ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="delete_current_avatar" name="delete_current_avatar">
                                    <label class="form-check-label" for="delete_current_avatar">Supprimer l'avatar actuel</label>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <input type="file" class="form-control mt-2" id="avatar" name="avatar" accept="image/*">
                            <small class="form-text text-muted">Formats acceptés : JPG, PNG, GIF. Taille maximale : 2MB.</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            <a href="profile.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../includes/journalist_footer.php'; // Assurez-vous d'avoir un footer spécifique pour les journalistes ?> 