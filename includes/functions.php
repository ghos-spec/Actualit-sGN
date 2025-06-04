<?php
/**
 * Helper functions for the news website
 */

/**
 * Get article by ID
 * 
 * @param PDO $conn Database connection
 * @param int $articleId Article ID
 * @param int|null $journalistId Optional journalist ID to check ownership
 * @return array|false Article data or false if not found
 */
function getArticleById($conn, $articleId, $journalistId = null) {
    // Assurer que $articleId est un entier pour éviter toute injection SQL ou type inattendu
    $articleId = (int)$articleId;
    
    error_log("Attempting to retrieve article with ID: " . $articleId . " (Journalist ID: " . ($journalistId ?? 'N/A') . ")");
    
    // Requête SQL pour récupérer l'article et les informations jointes des catégories et journalistes
    $sql = "SELECT a.*, c.name as category_name, c.id as category_id, c.slug as category_slug,
            j.name as journalist_name, j.email as journalist_email, j.avatar as journalist_avatar,
            j.bio as journalist_bio, j.title as journalist_title
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN journalists j ON a.journalist_id = j.id
            WHERE a.id = ?";
            
    error_log("DEBUG getArticleById - SQL Query: " . $sql);

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Database query preparation failed for article ID " . $articleId . ": " . print_r($conn->errorInfo(), true));
        return false; // Erreur de préparation de la requête
    }
    
    // Exécuter la requête avec l'ID de l'article comme paramètre lié
    $stmt->execute([$articleId]);
    
    // Récupérer l'article résultat
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'article existe
    if (!$article) {
        error_log("Article not found in database for ID: " . $articleId);
        return false; // Article non trouvé
    }
    
    error_log("Article found with ID: " . $article['id'] . ", Title: " . $article['title'] . ", Status: " . $article['status']);
    
    // Vérifier les conditions d'accès : l'article doit être publié, sauf si l'utilisateur est le journaliste propriétaire
    $isOwner = ($journalistId !== null && $article['journalist_id'] == $journalistId);
    
    if (!$isOwner && $article['status'] !== 'published') {
        error_log("Access denied for article ID " . $articleId . ": Not published and user is not owner.");
        return false; // Article non publié et utilisateur non propriétaire
    }
    
    error_log("Successfully retrieved and verified article ID: " . $article['id']);
    
    // Retourner les données de l'article si tout est bon
    return $article;
}

/**
 * Get featured articles
 * 
 * @param PDO $conn Database connection
 * @param int $limit Number of articles to return
 * @return array Featured articles
 */
function getFeaturedArticles($conn, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN journalists j ON a.journalist_id = j.id
        WHERE a.is_featured = 1 AND a.status = 'published'
        ORDER BY a.published_date DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get breaking news articles
 * 
 * @param PDO $conn Database connection
 * @param int $limit Number of articles to return
 * @return array Breaking news articles
 */
function getBreakingNews($conn, $limit = 4) {
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN journalists j ON a.journalist_id = j.id
        WHERE a.is_breaking = 1 AND a.status = 'published'
        ORDER BY a.published_date DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get articles by category
 * 
 * @param PDO $conn Database connection
 * @param int $categoryId Category ID
 * @param int $limit Number of articles to return
 * @param int $offset Offset for pagination
 * @return array Articles in the category
 */
function getArticlesByCategory($conn, $categoryId, $limit = 4, $offset = 0) {
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN journalists j ON a.journalist_id = j.id
        WHERE a.category_id = ? AND a.status = 'published'
        ORDER BY a.published_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$categoryId, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get category by slug
 * 
 * @param PDO $conn Database connection
 * @param string $slug Category slug
 * @return array|false Category data or false if not found
 */
function getCategoryBySlug($conn, $slug) {
    error_log("Tentative de récupérer la catégorie par slug : " . $slug);
    
    // Vérifier que le slug n'est pas vide
    if (empty($slug)) {
        error_log("Erreur : Le slug est vide");
        return false;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM categories
            WHERE slug = ?
        ");
        
        if (!$stmt) {
            error_log("Erreur de préparation de la requête : " . print_r($conn->errorInfo(), true));
            return false;
        }
        
        $stmt->execute([$slug]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Résultat pour le slug " . $slug . ": " . print_r($category, true));
        
        if (!$category) {
            error_log("Aucune catégorie trouvée pour le slug : " . $slug);
            return false;
        }
        
        return $category;
    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la récupération de la catégorie : " . $e->getMessage());
        return false;
    }
}

/**
 * Get count of articles in a category
 * 
 * @param PDO $conn Database connection
 * @param int $categoryId Category ID
 * @return int Number of articles
 */
function getCategoryArticleCount($conn, $categoryId) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM articles
        WHERE category_id = ? AND status = 'published'
    ");
    $stmt->execute([$categoryId]);
    return $stmt->fetchColumn();
}

/**
 * Get related articles
 * 
 * @param PDO $conn Database connection
 * @param int $articleId Current article ID
 * @param int $categoryId Category ID
 * @param int $limit Number of articles to return
 * @return array Related articles
 */
function getRelatedArticles($conn, $articleId, $categoryId, $limit = 3) {
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN journalists j ON a.journalist_id = j.id
        WHERE a.category_id = ? AND a.id != ? AND a.status = 'published'
        ORDER BY a.published_date DESC
        LIMIT ?
    ");
    $stmt->execute([$categoryId, $articleId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get popular articles
 * 
 * @param PDO $conn Database connection
 * @param int $limit Number of articles to return
 * @return array Popular articles
 */
function getPopularArticles($conn, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN journalists j ON a.journalist_id = j.id
        WHERE a.status = 'published'
        ORDER BY a.views DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update article view count
 * 
 * @param PDO $conn Database connection
 * @param int $articleId Article ID
 * @return bool Success status
 */
function updateArticleViews($conn, $articleId) {
    $stmt = $conn->prepare("
        UPDATE articles 
        SET views = views + 1 
        WHERE id = ?
    ");
    return $stmt->execute([$articleId]);
}

/**
 * Search articles
 * 
 * @param PDO $conn Database connection
 * @param string $query Search query
 * @param int $limit Number of articles to return
 * @param int $offset Offset for pagination
 * @return array Search results
 */
function searchArticles($conn, $query, $limit = 10, $offset = 0) {
    $searchTerm = "%$query%";
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN journalists j ON a.journalist_id = j.id
        WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?) AND a.status = 'published'
        ORDER BY a.published_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count search results
 * 
 * @param PDO $conn Database connection
 * @param string $query Search query
 * @return int Number of results
 */
function countSearchResults($conn, $query) {
    $searchTerm = "%$query%";
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM articles a
        WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?) AND a.status = 'published'
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    return $stmt->fetchColumn();
}

/**
 * Get a setting value
 * 
 * @param PDO $conn Database connection
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value
 */
function getSetting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT value FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['value'] : $default;
}

/**
 * Get the site title from settings
 * 
 * @param PDO $conn Database connection
 * @return string Site title
 */
function getSiteTitle($conn) {
    return getSetting($conn, 'site_title', 'Mon Site d\'Actualités');
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param bool $withTime Include time
 * @return string Formatted date
 */
function formatDate($date, $withTime = false) {
    $format = DATE_FORMAT;
    if ($withTime) {
        $format .= ' H:i';
    }
    // Handle potential invalid date strings
    if ($date === null || strtotime($date) === false) {
        return 'Date invalide';
    }
    return date($format, strtotime($date));
}

/**
 * Limit text to a certain number of words
 * 
 * @param string $text Text to limit
 * @param int $limit Word limit
 * @param string $ellipsis Ellipsis to append
 * @return string Limited text
 */
function limitWords($text, $limit, $ellipsis = '...') {
    $words = explode(' ', $text);
    if (count($words) > $limit) {
        return implode(' ', array_slice($words, 0, $limit)) . $ellipsis;
    }
    return $text;
}

/**
 * Get current URL
 * 
 * @return string Current URL
 */
function getCurrentUrl() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get latest articles
 * 
 * @param PDO $conn Database connection
 * @param int $limit Number of articles to return
 * @return array Latest articles
 */
function getLatestArticles($conn, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN journalists j ON a.journalist_id = j.id
        WHERE a.status = 'published'
        ORDER BY a.published_date DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get all categories
 * 
 * @param PDO $conn Database connection
 * @return array All categories
 */
function getAllCategories($conn) {
    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get journalist by ID
 * 
 * @param PDO $conn Database connection
 * @param int $id Journalist ID
 * @return array|false Journalist data or false if not found
 */
function getJournalistById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM journalists WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get articles by journalist
 * 
 * @param PDO $conn Database connection
 * @param int $journalistId Journalist ID
 * @param int $limit Number of articles to return
 * @param int $offset Offset for pagination
 * @return array Articles by journalist
 */
function getArticlesByJournalist($conn, $journalistId, $limit = 10, $offset = 0) {
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN journalists j ON a.journalist_id = j.id
        WHERE a.journalist_id = ? AND a.status = 'published'
        ORDER BY a.published_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$journalistId, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Nettoie une chaîne de caractères pour éviter les injections XSS
 * @param string $input La chaîne à nettoyer
 * @return string La chaîne nettoyée
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get comments for an article with optional filtering and pagination
 *
 * @param PDO $conn Database connection
 * @param int $articleId Article ID
 * @param string|null $status Comment status (e.g., 'approved', 'pending'). Use null for all.
 * @param int|null $parentId Parent comment ID for replies. Use null for top-level comments.
 * @param int $page Page number for pagination
 * @param int $perPage Number of comments per page
 * @param int|null $journalistId Optional journalist ID to include pending comments for their articles
 * @return array List of comments
 */
function getCommentsByArticleId($conn, $articleId, $status = 'approved', $parentId = null, $page = 1, $perPage = 10, $journalistId = null) {
    $offset = ($page - 1) * $perPage;
    
    // Base query with DISTINCT to avoid duplicates
    $sql = "SELECT DISTINCT c.id, c.article_id, c.journalist_id, c.author_name, c.author_email, c.content, c.parent_id, c.status, c.report_count, c.report_reason, c.likes_count, c.dislikes_count, c.created_at, c.updated_at, 
                   j.name as journalist_name, j.avatar as journalist_avatar 
            FROM comments c 
            LEFT JOIN journalists j ON c.journalist_id = j.id 
            WHERE c.article_id = ?";
    $params = [$articleId];

    // If a journalist is logged in and is the author of the article, show all comments
    if ($journalistId !== null) {
        $article = getArticleById($conn, $articleId);
        if ($article && $article['journalist_id'] == $journalistId) {
            // No status filter needed for the journalist
        } else {
            // If not the author, only show approved comments
            $sql .= " AND c.status = 'approved'";
        }
    } elseif ($status !== null) {
        $sql .= " AND c.status = ?";
        $params[] = $status;
    }

    // Handle parent_id condition correctly
    if ($parentId === null) {
        $sql .= " AND c.parent_id IS NULL";
    } else {
        $sql .= " AND c.parent_id = ?";
        $params[] = $parentId;
    }

    // Add ordering and pagination
    $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    // Log the executed query and parameters for debugging
    ob_start();
    $stmt->debugDumpParams();
    $dump = ob_get_clean();
    error_log("DEBUG getCommentsByArticleId: " . $dump);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Add a new comment
 *
 * @param PDO $conn Database connection
 * @param int $articleId Article ID
 * @param string|int $author Author name or journalist ID
 * @param string $content Comment content
 * @param string|null $authorEmail Comment author email (only for non-journalist comments)
 * @param int|null $parentId Parent comment ID for replies
 * @return int|bool Comment ID on success, false on failure
 */
function addComment($conn, $articleId, $author, $content, $authorEmail = null, $parentId = null) {
    try {
        $conn->beginTransaction();
        
        // Determine if author is a journalist ID or name
        $isJournalist = is_numeric($author);
        $journalistId = $isJournalist ? $author : null;
        $authorName = $isJournalist ? null : $author;
        
        // Get auto-approve setting
        $stmt = $conn->prepare("SELECT auto_approve_comments FROM moderation_settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $status = $settings['auto_approve_comments'] ? 'approved' : 'pending';
        
        // Insert the comment
        $stmt = $conn->prepare("
            INSERT INTO comments (
                article_id, 
                journalist_id, 
                author_name, 
                author_email, 
                content, 
                parent_id, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $articleId,
            $journalistId,
            $authorName,
            $authorEmail,
            $content,
            $parentId,
            $status
        ]);
        
        $commentId = $conn->lastInsertId();
        
        // Create notification for article author if comment is from a different journalist
        if ($isJournalist) {
            $article = getArticleById($conn, $articleId);
            if ($article && $article['journalist_id'] != $journalistId) {
                createNotification(
                    $article['journalist_id'],
                    $parentId ? 'reply' : 'comment',
                    "Nouveau commentaire sur votre article : " . $article['title'],
                    "article.php?id=" . $articleId
                );
            }
        }
        
        $conn->commit();
        return $commentId;
    } catch (Exception $e) {
        $conn->rollBack();
        // Log the error for debugging
        error_log("Add Comment Error: " . $e->getMessage());
        return false;
    }
}

function getCommentReplies($commentId, $page = 1, $perPage = 5) {
    return getCommentsByArticleId(null, $commentId, $page, $perPage);
}

function reportComment($commentId, $reason) {
    global $db;
    
    $sql = "UPDATE comments 
            SET report_count = report_count + 1,
                report_reason = CONCAT(COALESCE(report_reason, ''), '\n', ?),
                status = CASE 
                    WHEN report_count + 1 >= (SELECT max_reports_before_hide FROM moderation_settings LIMIT 1)
                    THEN 'reported'
                    ELSE status
                END
            WHERE id = ?";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$reason, $commentId]);
    
    // Notifier les modérateurs si activé
    if (getModerationSetting('notify_on_reports')) {
        $comment = getCommentById($commentId);
        if ($comment) {
            notifyModerators(
                "Commentaire signalé",
                "Le commentaire #" . $commentId . " a été signalé. Raison : " . $reason
            );
        }
    }
}

function voteComment($commentId, $userId, $voteType) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Vérifier si l'utilisateur a déjà voté
        $sql = "SELECT vote_type FROM comment_votes 
                WHERE comment_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$commentId, $userId]);
        $existingVote = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingVote) {
            if ($existingVote['vote_type'] === $voteType) {
                // Retirer le vote
                $sql = "DELETE FROM comment_votes 
                        WHERE comment_id = ? AND user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$commentId, $userId]);
                
                // Mettre à jour les compteurs
                $sql = "UPDATE comments 
                        SET " . $voteType . "s_count = " . $voteType . "s_count - 1 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$commentId]);
            } else {
                // Changer le vote
                $sql = "UPDATE comment_votes 
                        SET vote_type = ? 
                        WHERE comment_id = ? AND user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$voteType, $commentId, $userId]);
                
                // Mettre à jour les compteurs
                $sql = "UPDATE comments 
                        SET " . $voteType . "s_count = " . $voteType . "s_count + 1,
                            " . ($voteType === 'like' ? 'dislikes' : 'likes') . "_count = " . 
                            ($voteType === 'like' ? 'dislikes' : 'likes') . "_count - 1 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$commentId]);
            }
        } else {
            // Ajouter un nouveau vote
            $sql = "INSERT INTO comment_votes (comment_id, user_id, vote_type) 
                    VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$commentId, $userId, $voteType]);
            
            // Mettre à jour les compteurs
            $sql = "UPDATE comments 
                    SET " . $voteType . "s_count = " . $voteType . "s_count + 1 
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$commentId]);
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function getCommentById($commentId) {
    global $db;
    $sql = "SELECT * FROM comments WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$commentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getModerationSetting($key) {
    global $db;
    $sql = "SELECT $key FROM moderation_settings LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function createNotification($userId, $type, $content, $link = null) {
    global $db;
    $sql = "INSERT INTO notifications (user_id, type, content, link) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$userId, $type, $content, $link]);
}

function notifyModerators($title, $message) {
    global $db;
    $sql = "INSERT INTO notifications (user_id, type, content) 
            SELECT id, 'moderation', ? 
            FROM journalists 
            WHERE role IN ('admin', 'superadmin')";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$title . ': ' . $message]);
}

function getUnreadNotificationsCount($userId) {
    global $db;
    $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE";
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function markNotificationAsRead($notificationId) {
    global $db;
    $sql = "UPDATE notifications SET is_read = TRUE WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$notificationId]);
}

/**
 * Get comment count for an article with optional status filter and journalist ownership check
 *
 * @param PDO $conn Database connection
 * @param int $articleId Article ID
 * @param string|null $status Comment status filter (null for all)
 * @param int|null $journalistId Optional journalist ID to count all comments for their articles
 * @return int Comment count
 */
function getCommentCount($conn, $articleId, $status = 'approved', $journalistId = null) {
     $sql = "SELECT COUNT(*) FROM comments WHERE article_id = ?";
    $params = [$articleId];

    // If a journalist is logged in and is the author of the article, count all comments
    if ($journalistId !== null && getArticleById($conn, $articleId)['journalist_id'] == $journalistId) {
        // No status filter needed for the journalist
    } elseif ($status !== null) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
     // Log the executed query and parameters for debugging
    ob_start();
    $stmt->debugDumpParams();
    $dump = ob_get_clean();
    error_log("DEBUG getCommentCount: " . $dump);

    return $stmt->fetchColumn();
}

function getCommentPages($articleId, $perPage = 10) {
    $total = getCommentCount($conn, $articleId);
    return ceil($total / $perPage);
}

/**
 * Met à jour le statut d'un commentaire
 * @param PDO $conn Connexion à la base de données
 * @param int $commentId ID du commentaire
 * @param string $status Nouveau statut ('approved', 'rejected', 'pending')
 * @return bool True si la mise à jour a réussi, false sinon
 */
function updateCommentStatus($conn, $commentId, $status) {
    try {
        $stmt = $conn->prepare("UPDATE comments SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $commentId]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du statut du commentaire: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un commentaire de la base de données
 * @param PDO $conn Connexion à la base de données
 * @param int $commentId ID du commentaire à supprimer
 * @return bool True si la suppression a réussi, false sinon
 */
function deleteComment($conn, $commentId) {
    try {
        // Vérifier d'abord si le commentaire existe
        $checkStmt = $conn->prepare("SELECT id FROM comments WHERE id = ?");
        $checkStmt->execute([$commentId]);
        if (!$checkStmt->fetch()) {
            error_log("Tentative de suppression d'un commentaire inexistant - ID: " . $commentId);
            return false;
        }

        // Supprimer d'abord les votes associés au commentaire
        $deleteVotesStmt = $conn->prepare("DELETE FROM comment_votes WHERE comment_id = ?");
        $deleteVotesStmt->execute([$commentId]);

        // Supprimer ensuite le commentaire
        $deleteStmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $result = $deleteStmt->execute([$commentId]);

        if ($result) {
            error_log("Commentaire supprimé avec succès - ID: " . $commentId);
        } else {
            error_log("Échec de la suppression du commentaire - ID: " . $commentId);
        }

        return $result;
    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la suppression du commentaire - ID: " . $commentId . " - Message: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Erreur générale lors de la suppression du commentaire - ID: " . $commentId . " - Message: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si un commentaire appartient à un article du journaliste
 * @param PDO $conn Connexion à la base de données
 * @param int $commentId ID du commentaire
 * @param int $journalistId ID du journaliste
 * @return bool True si le commentaire appartient à un article du journaliste, false sinon
 */
function isCommentOwnedByJournalist($conn, $commentId, $journalistId) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM comments c
            JOIN articles a ON c.article_id = a.id
            WHERE c.id = ? AND a.journalist_id = ?
        ");
        $stmt->execute([$commentId, $journalistId]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification de propriété du commentaire: " . $e->getMessage());
        return false;
    }
}

/**
 * Get comments for all articles by a journalist
 *
 * @param PDO $conn Database connection
 * @param int $journalistId Journalist ID
 * @param string|null $status Optional comment status filter
 * @param string|null $searchQuery Optional search query for content or author name
 * @param string $sortBy Column to sort by ('created_at' or 'status')
 * @param string $sortOrder Sort order ('asc' or 'desc')
 * @param int $limit Number of comments to return (for pagination)
 * @param int $offset Offset for pagination
 * @return array List of comments
 */
function getCommentsByJournalistArticles($conn, $journalistId, $status = null, $searchQuery = null, $sortBy = 'created_at', $sortOrder = 'desc', $limit = 10, $offset = 0) {
    // Get article IDs for the journalist
    // Ensure journalistId is an integer
    $journalistId = (int)$journalistId;

    $stmtArticleIds = $conn->prepare("SELECT id FROM articles WHERE journalist_id = ?");
    $stmtArticleIds->execute([$journalistId]);
    $articleIds = $stmtArticleIds->fetchAll(PDO::FETCH_COLUMN);

    if (empty($articleIds)) {
        return []; // No articles found for this journalist
    }

    // Build the SQL query to get comments with DISTINCT to avoid duplicates
    $sql = "SELECT DISTINCT c.*, a.title as article_title, a.id as article_id 
            FROM comments c 
            INNER JOIN articles a ON c.article_id = a.id
            WHERE c.article_id IN (" . implode(',', array_fill(0, count($articleIds), '?')) . ")";
    $params = $articleIds;

    if ($status !== null) {
        $sql .= " AND c.status = ?";
        $params[] = $status;
    }

    if ($searchQuery !== null) {
        $sql .= " AND (c.content LIKE ? OR c.author_name LIKE ?)";
        $searchTerm = "%" . $searchQuery . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Add sorting
    $sql .= " ORDER BY ";
    // Ensure sorting column is valid to prevent SQL injection
    $allowedSortColumns = ['created_at', 'status'];
    $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'created_at';
    $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';

    if ($sortBy === 'status') {
        $sql .= "c.status ";
    } else {
        // Default sort by date
        $sql .= "c.created_at ";
    }
    $sql .= $sortOrder;

    // Add pagination - LIMIT and OFFSET should be integers, PDO handles binding
    // Ensure limit and offset are integers
    $limit = (int)$limit;
    $offset = (int)$offset;

    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    
    // Log the final query and parameters before execution for debugging
    error_log("DEBUG getCommentsByJournalistArticles SQL: " . $sql);
    error_log("DEBUG getCommentsByJournalistArticles Params: " . json_encode($params));

    $stmt->execute($params);
    
    // Log the executed query parameters for debugging (using debugDumpParams is good)
    ob_start();
    $stmt->debugDumpParams();
    $dump = ob_get_clean();
    error_log("DEBUG getCommentsByJournalistArticles debugDumpParams: " . $dump);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count comments for all articles by a journalist
 *
 * @param PDO $conn Database connection
 * @param int $journalistId Journalist ID
 * @param string|null $status Optional comment status filter
 * @param string|null $searchQuery Optional search query for content or author name
 * @return int Total number of comments
 */
function countCommentsByJournalistArticles($conn, $journalistId, $status = null, $searchQuery = null) {
     // Ensure journalistId is an integer
     $journalistId = (int)$journalistId;

    // Get article IDs for the journalist
    $stmtArticleIds = $conn->prepare("SELECT id FROM articles WHERE journalist_id = ?");
    $stmtArticleIds->execute([$journalistId]);
    $articleIds = $stmtArticleIds->fetchAll(PDO::FETCH_COLUMN);

    if (empty($articleIds)) {
        return 0; // No articles found for this journalist
    }

    // Build the SQL query to count comments
    $sql = "SELECT COUNT(*) 
            FROM comments c 
            JOIN articles a ON c.article_id = a.id
            WHERE c.article_id IN (" . implode(',', array_fill(0, count($articleIds), '?')) . ")";
    $params = $articleIds;

    if ($status !== null) {
        $sql .= " AND c.status = ?";
        $params[] = $status;
    }

    if ($searchQuery !== null) {
        $sql .= " AND (c.content LIKE ? OR c.author_name LIKE ?)";
        $searchTerm = "%" . $searchQuery . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $stmt = $conn->prepare($sql);
    
     // Log the final query and parameters before execution for debugging
     error_log("DEBUG countCommentsByJournalistArticles SQL: " . $sql);
     error_log("DEBUG countCommentsByJournalistArticles Params: " . json_encode($params));

    $stmt->execute($params);

     // Log the executed query parameters for debugging (using debugDumpParams is good)
     ob_start();
     $stmt->debugDumpParams();
     $dump = ob_get_clean();
     error_log("DEBUG countCommentsByJournalistArticles debugDumpParams: " . $dump);

    return $stmt->fetchColumn();
}