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

// Vérifier si l'utilisateur actuel est Superadmin pour afficher les options sensibles
$is_superadmin = isSuperAdmin($conn);

// Traiter la suppression d'un utilisateur
if ($is_superadmin && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    // Empêcher la suppression du Superadmin actuel
    if ($user_id === $_SESSION['admin_id']) {
        $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        try {
            // Vérifier si l'utilisateur existe et n'est pas le seul Superadmin restant (si l'utilisateur supprimé est Superadmin)
            $stmt_check = $conn->prepare("SELECT role FROM admin_users WHERE id = ?");
            $stmt_check->execute([$user_id]);
            $user_to_delete = $stmt_check->fetch();

            if ($user_to_delete && $user_to_delete['role'] === 'Superadmin') {
                $stmt_superadmins = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE role = 'Superadmin'");
                $stmt_superadmins->execute();
                if ($stmt_superadmins->fetchColumn() <= 1) {
                     throw new Exception("Impossible de supprimer le dernier Superadmin.");
                }
            }

            // Supprimer l'avatar si existant
            $stmt_avatar = $conn->prepare("SELECT avatar FROM admin_users WHERE id = ?");
            $stmt_avatar->execute([$user_id]);
            $avatar = $stmt_avatar->fetchColumn();
            if (!empty($avatar) && file_exists('../' . $avatar)) {
                unlink('../' . $avatar);
            }

            // Supprimer l'utilisateur
            $stmt_delete = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt_delete->execute([$user_id]);
            
            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'delete_user', "Utilisateur ID: $user_id supprimé");
            
            $_SESSION['success'] = "L'utilisateur a été supprimé avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur : " . $e->getMessage();
        }
    }
    header('Location: users.php');
    exit;
}

// Récupérer tous les utilisateurs admin
$stmt = $conn->prepare("SELECT id, username, email, role, last_login, created_at FROM admin_users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

include 'includes/superadmin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestion des Utilisateurs Admin</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Utilisateurs</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Liste des Utilisateurs Admin</h3>
                     <?php if ($is_superadmin): // Seul le Superadmin peut ajouter des utilisateurs ?>
                        <div class="card-tools">
                            <a href="add_user.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus"></i> Nouvel Utilisateur Admin
                            </a>
                        </div>
                     <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom d'utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Dernière connexion</th>
                                    <th>Date d'inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                         <td><?= htmlspecialchars($user['role'] ?? 'Journaliste') ?></td> <?php // Afficher 'Journaliste' par défaut si le rôle n'est pas défini ?>
                                        <td><?= isset($user['last_login']) && $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?></td>
                                        <td><?= isset($user['created_at']) && $user['created_at'] ? date('d/m/Y', strtotime($user['created_at'])) : 'N/A' ?></td>
                                        <td>
                                            <?php if ($is_superadmin || $user['id'] === $_SESSION['admin_id']): // Permettre l'édition par Superadmin ou l'utilisateur lui-même ?>
                                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-info btn-sm">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($is_superadmin && $user['id'] !== $_SESSION['admin_id']): // Seul le Superadmin peut supprimer, et ne peut pas se supprimer lui-même ?>
                                                <form action="users.php" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                             <?php if ($is_superadmin): // Seul le Superadmin peut voir l'activité ?>
                                                <a href="user_activity.php?id=<?= $user['id'] ?>" class="btn btn-secondary btn-sm" title="Voir l'activité">
                                                    <i class="bi bi-clock-history"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/superadmin_footer.php'; ?> 