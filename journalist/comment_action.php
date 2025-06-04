<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Vérifier si connecté et rôle journaliste
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['journalist_id']) || $_SESSION['journalist_role'] !== 'journaliste') {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé.']);
    exit;
}

$journalistId = $_SESSION['journalist_id'];

// Récupérer les données POST
$commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

// Valider les données
if (!$commentId || !in_array($action, ['approve', 'reject', 'delete'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants ou invalides.']);
    exit;
}

try {
    // Vérifier que le commentaire appartient à un article du journaliste
    if (!isCommentOwnedByJournalist($conn, $commentId, $journalistId)) {
        echo json_encode(['success' => false, 'error' => 'Vous n\'êtes pas autorisé à gérer ce commentaire.']);
        exit;
    }

    $success = false;
    $message = '';

    // Assurer que le commentaire appartient bien à l'un des articles du journaliste connecté
    // Cela nécessite une fonction qui vérifie cela.
    // Pour l'instant, nous allons simplement exécuter l'action.
    // NOTE: Il est crucial d'implémenter une vérification de propriété pour la sécurité.

    switch ($action) {
        case 'approve':
            $success = updateCommentStatus($conn, $commentId, 'approved');
            $message = $success ? 'Commentaire approuvé.' : 'Erreur lors de l\'approbation.';
            break;
        case 'reject':
            $success = updateCommentStatus($conn, $commentId, 'rejected');
            $message = $success ? 'Commentaire rejeté.' : 'Erreur lors du rejet.';
            break;
        case 'delete':
            $success = deleteComment($conn, $commentId);
            $message = $success ? 'Commentaire supprimé.' : 'Erreur lors de la suppression.';
            break;
    }

    // Log pour le débogage
    error_log("Action sur commentaire - ID: $commentId, Action: $action, Succès: " . ($success ? 'Oui' : 'Non'));

    // Envoyer la réponse JSON
    echo json_encode([
        'success' => $success, 
        'message' => $message,
        'debug' => [
            'comment_id' => $commentId,
            'action' => $action,
            'journalist_id' => $journalistId
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la gestion du commentaire: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Une erreur est survenue lors du traitement de votre demande.',
        'debug' => [
            'error_message' => $e->getMessage(),
            'comment_id' => $commentId,
            'action' => $action
        ]
    ]);
} catch (Exception $e) {
    error_log("Erreur générale lors de la gestion du commentaire: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Une erreur inattendue est survenue.',
        'debug' => [
            'error_message' => $e->getMessage(),
            'comment_id' => $commentId,
            'action' => $action
        ]
    ]);
}
?> 