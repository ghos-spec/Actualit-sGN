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
$userActivity = [];
$target_user_id = (int)($_GET['id'] ?? 0);
$targetUserData = null;

// Récupérer les informations de l'utilisateur dont on veut voir l'activité
if ($target_user_id > 0) {
    $stmt_user = $conn->prepare("SELECT id, username, role FROM admin_users WHERE id = ?");
    $stmt_user->execute([$target_user_id]);
    $targetUserData = $stmt_user->fetch();

    if (!$targetUserData) {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header('Location: users.php');
        exit;
    }

    // Récupérer l'historique des actions pour cet utilisateur
    $stmt_activity = $conn->prepare("SELECT * FROM admin_logs WHERE admin_id = ? ORDER BY timestamp DESC");
    $stmt_activity->execute([$target_user_id]);
    $userActivity = $stmt_activity->fetchAll();

} else {
    $_SESSION['error'] = "ID utilisateur manquant.";
    header('Location: users.php');
    exit;
}

include 'includes/superadmin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Activité de l'utilisateur : <?= htmlspecialchars($targetUserData['username']) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Utilisateurs</a></li>
                        <li class="breadcrumb-item active">Activité</li>
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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Historique des actions</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($userActivity)): ?>
                        <div class="alert alert-info">Aucune activité enregistrée pour cet utilisateur.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date & Heure</th>
                                        <th>Action</th>
                                        <th>Détails</th>
                                        <th>Adresse IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userActivity as $activity): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i:s', strtotime($activity['timestamp'])) ?></td>
                                            <td><?= htmlspecialchars($activity['action']) ?></td>
                                            <td><?= htmlspecialchars($activity['details']) ?></td>
                                            <td><?= htmlspecialchars($activity['ip_address']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/superadmin_footer.php'; ?> 