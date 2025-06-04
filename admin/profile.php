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

// Récupérer les informations de l'utilisateur connecté
$userData = getAdminUserById($conn, $_SESSION['admin_id']);

// Récupérer l'historique des connexions récentes
$stmt_login_history = $conn->prepare("
    SELECT login_time, ip_address, user_agent 
    FROM login_history 
    WHERE user_id = ? 
    ORDER BY login_time DESC 
    LIMIT 5
");
$stmt_login_history->execute([$_SESSION['admin_id']]);
$login_history = $stmt_login_history->fetchAll();

// Récupérer les statistiques d'activité
$stmt_stats = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM articles WHERE journalist_id = ?) as total_articles,
        (SELECT COUNT(*) FROM articles WHERE journalist_id = ? AND status = 'published') as published_articles,
        (SELECT COUNT(*) FROM articles WHERE journalist_id = ? AND status = 'draft') as draft_articles
");
$stmt_stats->execute([$_SESSION['admin_id'], $_SESSION['admin_id'], $_SESSION['admin_id']]);
$stats = $stmt_stats->fetch();

// Traiter le formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_account'])) {
        // Vérifier le mot de passe avant de supprimer le compte
        $password = $_POST['confirm_password'] ?? '';
        if (empty($password)) {
            $error = 'Veuillez entrer votre mot de passe pour confirmer la suppression.';
        } elseif (!password_verify($password, $userData['password'])) {
            $error = 'Mot de passe incorrect.';
        } else {
            try {
                // Supprimer l'avatar si existant
                if (!empty($userData['avatar']) && file_exists('../' . $userData['avatar'])) {
                    unlink('../' . $userData['avatar']);
                }

                // Supprimer l'utilisateur
                $stmt_delete = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
                $stmt_delete->execute([$_SESSION['admin_id']]);

                // Déconnecter l'utilisateur
                session_destroy();
                header('Location: login.php?message=' . urlencode('Votre compte a été supprimé avec succès.'));
                exit;
            } catch (Exception $e) {
                $error = 'Une erreur est survenue lors de la suppression du compte : ' . $e->getMessage();
            }
        }
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $title = sanitizeInput($_POST['title'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $current_avatar = sanitizeInput($_POST['current_avatar'] ?? '');
        $notifications = isset($_POST['notifications']) ? 1 : 0;
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;

        // Validation
        if (empty($username) || empty($email)) {
            $error = 'Le nom d\'utilisateur et l\'email sont obligatoires.';
        } elseif (!empty($password) && $password !== $confirm_password) {
            $error = 'Les mots de passe ne correspondent pas.';
        } elseif (!empty($password) && strlen($password) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } else {
            try {
                // Vérifier si l'email existe déjà pour un autre utilisateur
                $stmt_email = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ? AND id != ?");
                $stmt_email->execute([$email, $_SESSION['admin_id']]);
                if ($stmt_email->fetchColumn() > 0) {
                    throw new Exception('Cet email est déjà utilisé par un autre utilisateur.');
                }

                // Gérer l'upload de l'avatar
                $avatar_path = $current_avatar;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $new_avatar_path = uploadFile($_FILES['avatar'], 'uploads/avatars');
                    if ($new_avatar_path) {
                        if (!empty($current_avatar) && file_exists('../' . $current_avatar)) {
                            unlink('../' . $current_avatar);
                        }
                        $avatar_path = $new_avatar_path;
                    } else {
                        throw new Exception('Erreur lors du téléversement de l\'avatar.');
                    }
                }

                // Construire la requête de mise à jour
                $sql = "UPDATE admin_users SET username = ?, email = ?, title = ?, bio = ?, avatar = ?, notifications = ?, email_notifications = ?";
                $params = [$username, $email, $title, $bio, $avatar_path, $notifications, $email_notifications];

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql .= ", password = ?";
                    $params[] = $hashed_password;
                }

                $sql .= " WHERE id = ?";
                $params[] = $_SESSION['admin_id'];

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);

                $success = 'Profil mis à jour avec succès.';

                // Recharger les informations de l'utilisateur
                $userData = getAdminUserById($conn, $_SESSION['admin_id']);

                // Mettre à jour les informations de session
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_email'] = $email;

                // Journaliser l'action
                logAdminAction($conn, $_SESSION['admin_id'], 'update_profile', 'Profil mis à jour.');

            } catch (Exception $e) {
                $error = 'Une erreur est survenue : ' . $e->getMessage();
                logAdminAction($conn, $_SESSION['admin_id'], 'update_profile_error', 'Erreur lors de la mise à jour du profil : ' . $e->getMessage());
            }
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
                    <h1 class="m-0">Mon Profil</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Mon Profil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Modifier mes informations</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= $success ?></div>
                            <?php endif; ?>

                            <form action="profile.php" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="username">Nom d'utilisateur *</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($userData['username']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="title">Titre</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($userData['title'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label for="bio">Biographie</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="avatar">Avatar actuel</label><br>
                                    <?php if (!empty($userData['avatar'])): ?>
                                        <img src="../<?= htmlspecialchars($userData['avatar']) ?>" alt="Avatar actuel" style="max-width: 100px; margin-bottom: 10px; border-radius: 50%;"><br>
                                        <input type="hidden" name="current_avatar" value="<?= htmlspecialchars($userData['avatar']) ?>">
                                    <?php endif; ?>
                                    <label for="avatar">Choisir un nouvel avatar</label>
                                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                                </div>

                                <div class="form-group">
                                    <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="notifications" name="notifications" <?= ($userData['notifications'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="notifications">Activer les notifications</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="email_notifications" name="email_notifications" <?= ($userData['email_notifications'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="email_notifications">Recevoir les notifications par email</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Section Statistiques -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Mes Statistiques</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info"><i class="fas fa-newspaper"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Articles Totaux</span>
                                            <span class="info-box-number"><?= $stats['total_articles'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Articles Publiés</span>
                                            <span class="info-box-number"><?= $stats['published_articles'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-pencil-alt"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Brouillons</span>
                                            <span class="info-box-number"><?= $stats['draft_articles'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Historique des connexions -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Historique des connexions récentes</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Adresse IP</th>
                                            <th>Navigateur</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($login_history as $login): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($login['login_time'])) ?></td>
                                                <td><?= htmlspecialchars($login['ip_address']) ?></td>
                                                <td><?= htmlspecialchars($login['user_agent']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Section Suppression du compte -->
                    <div class="card mt-4">
                        <div class="card-header bg-danger">
                            <h3 class="card-title">Zone dangereuse</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-danger">La suppression de votre compte est irréversible. Toutes vos données seront définitivement supprimées.</p>
                            <form action="profile.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.');">
                                <div class="form-group">
                                    <label for="delete_confirm_password">Entrez votre mot de passe pour confirmer</label>
                                    <input type="password" class="form-control" id="delete_confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="delete_account" class="btn btn-danger">Supprimer mon compte</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Informations du compte</h3>
                        </div>
                        <div class="card-body text-center">
                            <!-- Photo de profil -->
                            <div class="profile-image mb-4">
                                <?php 
                                // Débogage
                                echo "<!-- Debug: Avatar path = " . htmlspecialchars($userData['avatar'] ?? 'null') . " -->";
                                if (!empty($userData['avatar'])): 
                                    $avatarPath = '../' . $userData['avatar'];
                                    echo "<!-- Debug: Full path = " . htmlspecialchars($avatarPath) . " -->";
                                    echo "<!-- Debug: File exists = " . (file_exists($avatarPath) ? 'yes' : 'no') . " -->";
                                ?>
                                    <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Photo de profil" class="img-circle elevation-2" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="../assets/img/default-avatar.png" alt="Photo de profil par défaut" class="img-circle elevation-2" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="mb-3"><?= htmlspecialchars($userData['username']) ?></h4>
                            <?php if (!empty($userData['title'])): ?>
                                <p class="text-muted mb-3"><?= htmlspecialchars($userData['title']) ?></p>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="text-left">
                                <p><strong>Rôle :</strong> <?= htmlspecialchars($userData['role'] ?? 'Non défini') ?></p>
                                <p><strong>Email :</strong> <?= htmlspecialchars($userData['email']) ?></p>
                                <p><strong>Dernière connexion :</strong> <?= isset($userData['last_login']) && $userData['last_login'] ? date('d/m/Y H:i', strtotime($userData['last_login'])) : 'Jamais' ?></p>
                                <p><strong>Compte créé le :</strong> <?= isset($userData['created_at']) && $userData['created_at'] ? date('d/m/Y H:i', strtotime($userData['created_at'])) : 'Non disponible' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 