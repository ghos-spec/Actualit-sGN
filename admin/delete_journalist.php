<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once 'includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté et est superadmin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !isSuperAdmin($conn)) {
    $_SESSION['error'] = "Accès non autorisé. Seul le superadmin peut supprimer les journalistes.";
    header('Location: index.php');
    exit;
}

// Vérifier si l'ID du journaliste est fourni
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID du journaliste non spécifié.";
    header('Location: journalists.php');
    exit;
}

$journalist_id = (int)$_GET['id'];

try {
    // Vérifier si le journaliste existe
    $stmt = $conn->prepare("SELECT * FROM journalists WHERE id = ?");
    $stmt->execute([$journalist_id]);
    $journalist = $stmt->fetch();

    if (!$journalist) {
        throw new Exception("Journaliste non trouvé.");
    }

    // Vérifier si le journaliste a des articles
    $stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE journalist_id = ?");
    $stmt->execute([$journalist_id]);
    $article_count = $stmt->fetchColumn();

    if ($article_count > 0) {
        throw new Exception("Impossible de supprimer ce journaliste car il a des articles associés.");
    }

    // Supprimer l'avatar s'il existe
    if ($journalist['avatar'] && file_exists('../' . $journalist['avatar'])) {
        unlink('../' . $journalist['avatar']);
    }

    // Supprimer le journaliste
    $stmt = $conn->prepare("DELETE FROM journalists WHERE id = ?");
    $stmt->execute([$journalist_id]);

    // Journaliser l'action
    logAdminAction($conn, $_SESSION['admin_id'], 'delete_journalist', "Journaliste supprimé : " . $journalist['name']);

    $_SESSION['success'] = "Le journaliste a été supprimé avec succès.";
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la suppression du journaliste : " . $e->getMessage();
}

header('Location: journalists.php');
exit; 