<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Vérifier si connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['journalist_id']) || strtolower($_SESSION['journalist_role']) !== 'journaliste') {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de l'article est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "ID d'article invalide.";
    $_SESSION['flash_type'] = "danger";
    header('Location: articles.php');
    exit;
}

$article_id = (int)$_GET['id'];

try {
    // Vérifier si l'article appartient au journaliste
    $stmt = $conn->prepare("SELECT id, image_path FROM articles WHERE id = ? AND journalist_id = ?");
    $stmt->execute([$article_id, $_SESSION['journalist_id']]);
    
    $article = $stmt->fetch();
    if (!$article) {
        $_SESSION['flash_message'] = "Article non trouvé ou vous n'avez pas les droits pour le supprimer.";
        $_SESSION['flash_type'] = "danger";
        header('Location: articles.php');
        exit;
    }

    // Supprimer l'image associée si elle existe
    if (!empty($article['image_path'])) {
        $image_path = '../' . $article['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Supprimer l'article
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$article_id]);

    $_SESSION['flash_message'] = "L'article a été supprimé avec succès.";
    $_SESSION['flash_type'] = "success";
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Une erreur est survenue lors de la suppression de l'article.";
    $_SESSION['flash_type'] = "danger";
}

header('Location: articles.php');
exit; 