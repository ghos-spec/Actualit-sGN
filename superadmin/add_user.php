<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../admin/includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté et est Superadmin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !isSuperAdmin($conn)) {
    $_SESSION['error'] = "Accès non autorisé.";
    header('Location: ../admin/index.php');
    exit;
}

$error = '';
$success = '';

// Traiter le formulaire d'ajout d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'Journaliste'); // Rôle par défaut

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Le format de l\'email est invalide.';
    } else {
        try {
            // Vérifier si le nom d'utilisateur ou l'email existe déjà
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ? OR email = ?");
            $stmt_check->execute([$username, $email]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception('Ce nom d\'utilisateur ou cet email est déjà utilisé.');
            }

            // Insérer l'utilisateur
            $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$username, $email, $hashed_password, $role]);

            $user_id = $conn->lastInsertId();

            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'add_user', "Utilisateur admin ID: $user_id ($username) ajouté avec le rôle $role");

            $_SESSION['success'] = 'Utilisateur admin ajouté avec succès.';
            header('Location: users.php');
            exit;

        } catch (Exception $e) {
            $error = 'Une erreur est survenue : ' . $e->getMessage();
            logAdminAction($conn, $_SESSION['admin_id'], 'add_user_error', 'Erreur lors de l\'ajout d\'utilisateur admin : ' . $e->getMessage());
        }
    }
}

include 'includes/superadmin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Ajouter un Nouvel Utilisateur Admin</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Utilisateurs</a></li>
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
                    <h3 class="card-title">Informations de l'Utilisateur</h3>
                </div>
                <div class="card-body">
                    <form action="add_user.php" method="post">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur *</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                         <div class="form-group">
                            <label for="role">Rôle *</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="Journaliste" <?= (isset($_POST['role']) && $_POST['role'] == 'Journaliste') ? 'selected' : '' ?>>Journaliste</option>
                                <option value="Admin" <?= (isset($_POST['role']) && $_POST['role'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="Superadmin" <?= (isset($_POST['role']) && $_POST['role'] == 'Superadmin') ? 'selected' : '' ?>>Superadmin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Ajouter l'utilisateur</button>
                             <a href="users.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/superadmin_footer.php'; ?> 