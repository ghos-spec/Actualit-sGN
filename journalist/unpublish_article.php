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
    $stmt = $conn->prepare("SELECT id FROM articles WHERE id = ? AND journalist_id = ?");
    $stmt->execute([$article_id, $_SESSION['journalist_id']]);
    
    if (!$stmt->fetch()) {
        $_SESSION['flash_message'] = "Article non trouvé ou vous n'avez pas les droits pour le modifier.";
        $_SESSION['flash_type'] = "danger";
        header('Location: articles.php');
        exit;
    }

    // Mettre à jour le statut de l'article
    $stmt = $conn->prepare("UPDATE articles SET status = 'draft' WHERE id = ?");
    $stmt->execute([$article_id]);

    $_SESSION['flash_message'] = "L'article a été mis en brouillon avec succès.";
    $_SESSION['flash_type'] = "success";
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Une erreur est survenue lors de la mise en brouillon de l'article.";
    $_SESSION['flash_type'] = "danger";
}

header('Location: articles.php');
exit; 