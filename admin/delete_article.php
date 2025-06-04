<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once 'includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté et a les droits nécessaires (par exemple, un rôle admin)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Assurez-vous que la fonction isAdmin() ou équivalent existe et est appelée si nécessaire
// if (!isLoggedIn() || !isAdmin()) { 
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$article_id = null;

// Récupérer l'ID de l'article à supprimer depuis l'URL
if (isset($_GET['id'])) {
    $article_id = (int)$_GET['id'];

    try {
        // Optionnel: Supprimer l'image associée à l'article
        // $stmt_image = $conn->prepare("SELECT image_path FROM articles WHERE id = ?");
        // $stmt_image->execute([$article_id]);
        // $article_image = $stmt_image->fetchColumn();
        // if (!empty($article_image) && file_exists('../' . $article_image)) {
        //     unlink('../' . $article_image);
        // }

        // Supprimer l'article de la base de données
        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);

        // Vérifier si la suppression a réussi (optionnel mais recommandé)
        if ($stmt->rowCount() > 0) {
            // Journaliser l'action
            logAdminAction($conn, $_SESSION['admin_id'], 'delete_article', "Article supprimé avec l'ID: " . $article_id);
            // Rediriger avec un message de succès
            header('Location: articles.php?success=Article supprimé avec succès.');
            exit;
        } else {
            // Rediriger avec un message d'erreur si l'article n'a pas été trouvé
            header('Location: articles.php?error=Article non trouvé ou déjà supprimé.');
            exit;
        }

    } catch (PDOException $e) {
        // Rediriger avec un message d'erreur en cas de problème de base de données
        logAdminAction($conn, $_SESSION['admin_id'], 'delete_article_error', "Erreur lors de la suppression de l'article avec l'ID " . $article_id . ": " . $e->getMessage());
        header('Location: articles.php?error=Une erreur de base de données est survenue lors de la suppression.');
        exit;
    } catch (Exception $e) {
         // Rediriger avec un message d'erreur pour d'autres exceptions
        logAdminAction($conn, $_SESSION['admin_id'], 'delete_article_error', "Erreur inattendue lors de la suppression de l'article avec l'ID " . $article_id . ": " . $e->getMessage());
        header('Location: articles.php?error=Une erreur inattendue est survenue lors de la suppression.');
        exit;
    }

} else {
    // Si pas d'ID dans l'URL, rediriger vers la liste des articles avec un message d'erreur
    header('Location: articles.php?error=ID d\'article non spécifié.');
    exit;
}
?> 