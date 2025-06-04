<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../admin/includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn()) {
    header('Location: ../admin/login.php');
    exit;
}

$error = '';
$success = '';
$userData = null;
$user_id = (int)($_GET['id'] ?? 0);

// Vérifier si l'utilisateur actuel est Superadmin ou s'il modifie son propre compte
$is_superadmin = isSuperAdmin($conn);
$can_edit = $is_superadmin || ($user_id > 0 && $user_id === $_SESSION['admin_id']);

// Si l'utilisateur n'a pas les droits, rediriger
if (!$can_edit && $user_id !== $_SESSION['admin_id']) {
    $_SESSION['error'] = "Accès non autorisé à la modification de cet utilisateur.";
    header('Location: users.php');
    exit;
}

// Récupérer les informations de l'utilisateur à modifier
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $userData = $stmt->fetch();

    if (!$userData) {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header('Location: users.php');
        exit;
    }
} else {
    $_SESSION['error'] = "ID utilisateur manquant.";
    header('Location: users.php');
    exit;
}

// Traiter le formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? $userData['role']); // Garder le rôle existant par défaut
    $current_avatar = sanitizeInput($_POST['current_avatar'] ?? '');
    $avatar_path = $current_avatar;

    // Validation
    if (empty($username) || empty($email) || empty($role)) {
        $error = 'Le nom d\'utilisateur, l\'email et le rôle sont obligatoires.';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Le format de l\'email est invalide.';
    } else {
        try {
            // Vérifier si le nom d'utilisateur ou l'email existe déjà pour un autre utilisateur
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt_check->execute([$username, $email, $user_id]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception('Ce nom d\'utilisateur ou cet email est déjà utilisé par un autre compte.');
            }

            // Gérer l'upload de l'avatar
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_folder = 'uploads/avatars';
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2097152; // 2MB

                $new_avatar_path = uploadFile($_FILES['avatar'], $upload_folder, $allowed_types, $max_size);
                
                if ($new_avatar_path) {
                    // Supprimer l'ancien avatar si elle existe
                    if (!empty($current_avatar) && file_exists('../' . $current_avatar)) {
                        unlink('../' . $current_avatar);
                    }
                    $avatar_path = $new_avatar_path;
                } else {
                    throw new Exception('Erreur lors du téléversement de l\'avatar.');
                }
            } elseif (isset($_POST['delete_current_avatar'])) {
                 // Supprimer l'avatar actuel si la case est cochée
                 if (!empty($current_avatar) && file_exists('../' . $current_avatar)) {
                    unlink('../' . $current_avatar);
                    $avatar_path = null;
                 }
            }

            // Préparer la requête de mise à jour
            $sql = "UPDATE admin_users SET username = ?, email = ?, role = ?, avatar = ?";
            $params = [$username, $email, $role, $avatar_path];

            // Si un nouveau mot de passe est fourni, l'ajouter à la requête
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                $params[] = $hashed_password;
            }
            
            // Un Superadmin ne peut pas changer son propre rôle en quelque chose d'autre s'il est le dernier Superadmin
            if ($user_id === $_SESSION['admin_id'] && $userData['role'] === 'Superadmin' && $role !== 'Superadmin') {
                 $stmt_superadmins = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE role = 'Superadmin'");
                 $stmt_superadmins->execute();
                 if ($stmt_superadmins->fetchColumn() <= 1) {
                      throw new Exception("Impossible de changer votre propre rôle si vous êtes le dernier Superadmin.");
                 }
            }
             // Seul un Superadmin peut changer le rôle des autres utilisateurs
            if (!$is_superadmin && $role !== ($userData['role'] ?? 'Journaliste')) {
                // Si l'utilisateur n'est pas superadmin, il ne peut pas changer le rôle
                 $role = ($userData['role'] ?? 'Journaliste'); // Revenir à l'ancien rôle
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'edit_user', "Utilisateur admin ID: $user_id ($username) modifié. Rôle: $role");

            $_SESSION['success'] = 'Utilisateur admin mis à jour avec succès.';

            // Si l'utilisateur connecté a modifié son propre compte et changé son rôle en quelque chose d'autre que Superadmin
            // il peut être nécessaire de rediriger si l'accès à cette page dépend du rôle Superadmin.
            if ($user_id === $_SESSION['admin_id'] && $role !== 'Superadmin' && !$is_superadmin) {
                 header('Location: ../admin/index.php');
                 exit;
            }

            // Recharger les données de l'utilisateur après la mise à jour
            $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
            $stmt->execute([$user_id]);
            $userData = $stmt->fetch();

        } catch (Exception $e) {
            $error = 'Une erreur est survenue : ' . $e->getMessage();
            logAdminAction($conn, $_SESSION['admin_id'], 'edit_user_error', "Erreur lors de la mise à jour de l'utilisateur admin ID $user_id : " . $e->getMessage());
        }
    }
}

?>
<?php include 'includes/superadmin_header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Modifier l'Utilisateur Admin</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Utilisateurs</a></li>
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
                    <h3 class="card-title">Informations de l'Utilisateur</h3>
                </div>
                <div class="card-body">
                    <form action="edit_user.php?id=<?= $user_id ?>" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur *</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($userData['username'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="form-text text-muted">Entrez un nouveau mot de passe pour le changer. Minimum 6 caractères.</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <?php if ($is_superadmin): // Seul un Superadmin peut changer le rôle ?>
                            <div class="form-group">
                                <label for="role">Rôle *</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="Admin" <?= ($userData['role'] ?? '') === 'Admin' ? 'selected' : '' ?>>Administrateur</option>
                                    <option value="Superadmin" <?= ($userData['role'] ?? '') === 'Superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="role" value="<?= htmlspecialchars($userData['role'] ?? '') ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="avatar">Photo de profil (facultatif)</label>
                            <?php if (!empty($userData['avatar'])): ?>
                                <div class="mb-2">
                                    <img src="../<?= htmlspecialchars($userData['avatar']) ?>" alt="Avatar actuel" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="delete_current_avatar" name="delete_current_avatar">
                                    <label class="form-check-label" for="delete_current_avatar">Supprimer la photo actuelle</label>
                                </div>
                                <input type="hidden" name="current_avatar" value="<?= htmlspecialchars($userData['avatar']) ?>">
                            <?php endif; ?>
                            <input type="file" class="form-control mt-2" id="avatar" name="avatar" accept="image/*">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            <a href="users.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/superadmin_footer.php'; ?> 