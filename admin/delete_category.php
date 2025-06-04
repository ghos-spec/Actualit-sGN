<?php
require_once '../includes/config.php';
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

// Vérifier si un ID de catégorie est fourni dans l'URL
if (isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];

    try {
        // Vérifier si la catégorie existe avant de tenter de supprimer
        $stmt_check = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt_check->execute([$category_id]);

        if ($stmt_check->rowCount() === 0) {
             header('Location: categories.php?error=' . urlencode('Catégorie non trouvée.'));
             exit;
        }

        // Optionnel : Gérer les articles liés à cette catégorie
        // Décision : Laisser les articles orphelins (category_id = NULL) ou les attribuer à une catégorie par défaut, ou empêcher la suppression si des articles sont liés.
        // Pour l'instant, nous allons laisser les articles avec un category_id invalide. Une migration pourrait être nécessaire plus tard.

        // Supprimer la catégorie
        $stmt_delete = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt_delete->execute([$category_id]);

        if ($stmt_delete->rowCount() > 0) {
            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'delete_category', 'Catégorie avec l\'ID ' . $category_id . ' supprimée.');
            header('Location: categories.php?success=' . urlencode('Catégorie supprimée avec succès. Les articles associés pourraient avoir besoin d\'être mis à jour.'));
            exit;
        } else {
            header('Location: categories.php?error=' . urlencode('Erreur lors de la suppression de la catégorie ou catégorie déjà supprimée.'));
            exit;
        }

    } catch (Exception $e) {
        logAdminAction($conn, $_SESSION['admin_id'], 'delete_category_error', 'Erreur lors de la suppression de la catégorie ' . $category_id . ': ' . $e->getMessage());
        header('Location: categories.php?error=' . urlencode('Une erreur est survenue : ' . $e->getMessage()));
        exit;
    }

} else {
    // Si aucun ID n'est fourni, rediriger vers la liste des catégories
    header('Location: categories.php');
    exit;
}
?> 