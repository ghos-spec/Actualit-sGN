<?php

// Activer l'affichage de toutes les erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Log après les includes
error_log("DEBUG: Tous les fichiers inclus avec succès.");

// Fonction de logging améliorée
function logAjaxError($message) {
    $logFile = __DIR__ . '/../debug_ajax.log'; // Log dans un fichier séparé
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [get_comments.php] " . (is_array($message) ? json_encode($message, JSON_UNESCAPED_UNICODE) : $message) . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

logAjaxError("Requête AJAX reçue.");
logAjaxError("GET params: " . json_encode($_GET, JSON_UNESCAPED_UNICODE));

header('Content-Type: application/json');

// Démarrer la session si elle n'est pas déjà démarrée pour accéder à $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
logAjaxError("Session démarrée.");
logAjaxError("Session data: " . json_encode($_SESSION, JSON_UNESCAPED_UNICODE));

$articleId = filter_input(INPUT_GET, 'article_id', FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$perPage = filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT);

logAjaxError("Paramètres filtrés - articleId: $articleId, page: $page, perPage: $perPage");

if (!$articleId || !$page || !$perPage) {
    logAjaxError("Paramètres manquants ou invalides.");
    echo json_encode(['error' => 'Paramètres manquants ou invalides.']);
    exit;
}

try {
    // Check if a journalist is logged in and get their ID
    $loggedInJournalistId = isset($_SESSION['journalist_id']) ? (int)$_SESSION['journalist_id'] : null;
    logAjaxError("loggedInJournalistId: $loggedInJournalistId");

    // Determine status filter based on user role (journalist sees all on their articles, others see only approved)
    // Need article info to check ownership
    logAjaxError("Appel de getArticleById pour vérification de propriété.");
    $article = getArticleById($conn, $articleId); 
    
    if (!$article) {
        logAjaxError("Article non trouvé.");
        echo json_encode(['error' => 'Article non trouvé.']);
        exit;
    }

    $commentStatusFilter = ($loggedInJournalistId !== null && $article && $article['journalist_id'] == $loggedInJournalistId) ? null : 'approved';
    logAjaxError("Comment status filter: " . ($commentStatusFilter ?? 'null'));

    // Get comments for the requested page, considering journalist view
    logAjaxError("Appel de getCommentsByArticleId avec page $page et perPage $perPage.");
    $comments = getCommentsByArticleId($conn, $articleId, $commentStatusFilter, null, $page, $perPage, $loggedInJournalistId);
    logAjaxError("Nombre de commentaires récupérés: " . count($comments));

    $html = '';
    if ($comments) {
        foreach ($comments as $comment) {
            // Determine comment class based on status
            $commentClass = $comment['status'] === 'pending' ? 'comment-item pending' : 'comment-item';

            // Log comment data for debugging
            logAjaxError("Traitement Comment ID: " . $comment['id'] . ", Status: " . $comment['status']);

            $html .= '<div class="' . $commentClass . '" id="comment-' . $comment['id'] . '">';

            // Author Name with Icon
            $html .= '<div class="comment-author-info">';
            $html .= '<i class="bi bi-person-circle"></i>';
            $html .= '<span>' . htmlspecialchars($comment['author_name'] ?? '') . '</span>';
            $html .= '</div>';

            // Date and Status
            $html .= '<div class="comment-meta">';
            $html .= '<i class="bi bi-clock"></i>';
            $html .= '<span>Le ' . formatDate($comment['created_at']) . '</span>';

            // Show status only if a journalist is logged in and owns the article (logic from article.php)
            // Note: We need to pass $loggedInJournalistId and $article ownership check result here
            // For simplicity in this AJAX response, we'll assume the check was done before calling getCommentsByArticleId
            // and just display status if commentStatusFilter was null (meaning journalist view)
            if ($commentStatusFilter === null) { // If journalist view, show status
                 $html .= ' - Statut: <span class="status-badge status-' . $comment['status'] . '">' . ucfirst($comment['status']) . '</span>';
            }
            $html .= '</div>';

            // Optional Email with Icon
             if (!empty($comment['author_email'])) {
                 $html .= '<div class="comment-email" style="font-size: 0.8rem; color: #6c757d; margin-bottom: 5px;">';
                 $html .= '<i class="bi bi-envelope"></i>';
                 $html .= ' ' . htmlspecialchars($comment['author_email']);
                 $html .= '</div>';
             }

            // Comment Content
            $html .= '<div class="comment-content">' . nl2br(htmlspecialchars($comment['content'] ?? '')) . '</div>';

            // Action buttons for journalists (handled by separate AJAX, not needed in this HTML generation)

            $html .= '</div>';
        }
    }

    // Check if there are more comments to load, considering journalist view
    logAjaxError("Appel de getCommentCount.");
    $totalComments = getCommentCount($conn, $articleId, $commentStatusFilter, $loggedInJournalistId);
    $loadedCommentsCount = $page * $perPage;
    $hasMore = $totalComments > $loadedCommentsCount;
    logAjaxError("Total comments: $totalComments, Loaded comments: $loadedCommentsCount, Has more: " . ($hasMore ? 'Yes' : 'No'));

    echo json_encode([
        'html' => $html,
        'hasMore' => $hasMore
    ]);

} catch (Exception $e) {
    logAjaxError("Erreur dans get_comments.php: " . $e->getMessage());
    // Retourner une réponse JSON d'erreur générique pour ne pas exposer les détails de l'erreur à l'utilisateur final
    echo json_encode(['error' => 'Une erreur est survenue lors du chargement des commentaires.']);
}

?> 