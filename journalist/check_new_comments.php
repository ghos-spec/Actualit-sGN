<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Vérifier si connecté et rôle journaliste
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['journalist_id']) || $_SESSION['journalist_role'] !== 'journaliste') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$journalistId = $_SESSION['journalist_id'];

// Récupérer le timestamp de la dernière vérification
$lastCheck = isset($_GET['last_check']) ? intval($_GET['last_check']) : 0;

// Récupérer tous les articles du journaliste
$journalistArticles = getArticlesByJournalist($conn, $journalistId);
$hasNewComments = false;

// Vérifier s'il y a de nouveaux commentaires
foreach ($journalistArticles as $article) {
    $comments = getCommentsByArticleId($conn, $article['id'], null);
    foreach ($comments as $comment) {
        if (strtotime($comment['created_at']) > $lastCheck) {
            $hasNewComments = true;
            break 2; // Sortir des deux boucles
        }
    }
}

// Retourner le résultat en JSON
header('Content-Type: application/json');
echo json_encode([
    'hasNewComments' => $hasNewComments,
    'timestamp' => time()
]); 