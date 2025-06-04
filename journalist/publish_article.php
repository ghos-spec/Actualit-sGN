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
    // Vérifier si l'article appartient au journaliste et n'est pas déjà publié
    $stmt = $conn->prepare("SELECT id, status FROM articles WHERE id = ? AND journalist_id = ?");
    $stmt->execute([$article_id, $_SESSION['journalist_id']]);
    
    $article = $stmt->fetch();

    if (!$article) {
        $_SESSION['flash_message'] = "Article non trouvé ou vous n'avez pas les droits pour le modifier.";
        $_SESSION['flash_type'] = "danger";
        header('Location: articles.php');
        exit;
    }

    if ($article['status'] === 'published') {
        $_SESSION['flash_message'] = "L'article est déjà publié.";
        $_SESSION['flash_type'] = "info";
        header('Location: articles.php');
        exit;
    }

    // Obtenir la date et l'heure actuelles au format SQL
    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE articles SET status = 'published', published_at = ? WHERE id = ?");
    $stmt->execute([$current_time, $article_id]);

    $_SESSION['flash_message'] = "L'article a été publié avec succès.";
    $_SESSION['flash_type'] = "success";
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Une erreur est survenue lors de la publication de l'article.";
    $_SESSION['flash_type'] = "danger";
    // Log error for debugging
    error_log("Error publishing article: " . $e->getMessage());
    // Temporary: Display error on page for debugging
    $_SESSION['flash_message'] .= "<br>Détails de l'erreur : " . $e->getMessage();
} catch (Exception $e) {
    // Catch any other unexpected errors
    $_SESSION['flash_message'] = "Une erreur inattendue est survenue : " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
     error_log("Unexpected error publishing article: " . $e->getMessage());
}

header('Location: articles.php');
exit; 