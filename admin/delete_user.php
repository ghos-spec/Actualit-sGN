<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once 'includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté et a les droits nécessaires (par exemple, un rôle super-admin)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// if (!isLoggedIn() || !isSuperAdmin()) { // Uncomment and implement isSuperAdmin() if roles are used
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Vérifier si un ID d'utilisateur est fourni dans l'URL
if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    try {
        // Vérifier que l'utilisateur n'essaie pas de supprimer son propre compte
        if ($user_id === $_SESSION['admin_id']) {
            header('Location: users.php?error=' . urlencode('Vous ne pouvez pas supprimer votre propre compte.'));
            exit;
        }

        // Vérifier si l'utilisateur existe avant de tenter de supprimer
        $stmt_check = $conn->prepare("SELECT id FROM admin_users WHERE id = ?");
        $stmt_check->execute([$user_id]);

        if ($stmt_check->rowCount() === 0) {
             header('Location: users.php?error=' . urlencode('Utilisateur non trouvé.'));
             exit;
        }

        // Supprimer l'utilisateur
        $stmt_delete = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt_delete->execute([$user_id]);

        if ($stmt_delete->rowCount() > 0) {
            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'delete_user', 'Utilisateur admin avec l\'ID ' . $user_id . ' supprimé.');
            header('Location: users.php?success=' . urlencode('Utilisateur supprimé avec succès.'));
            exit;
        } else {
            header('Location: users.php?error=' . urlencode('Erreur lors de la suppression de l\'utilisateur ou utilisateur déjà supprimé.'));
            exit;
        }

    } catch (Exception $e) {
        logAdminAction($conn, $_SESSION['admin_id'], 'delete_user_error', 'Erreur lors de la suppression de l\'utilisateur admin ' . $user_id . ': ' . $e->getMessage());
        header('Location: users.php?error=' . urlencode('Une erreur est survenue : ' . $e->getMessage()));
        exit;
    }

} else {
    // Si aucun ID n'est fourni, rediriger vers la liste des utilisateurs
    header('Location: users.php');
    exit;
}
?> 