<?php
/**
 * Admin helper functions
 */

/**
 * Check if user is logged in
 * 
 * @return bool Login status
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Check if user is Superadmin
 * 
 * @param PDO $conn Database connection
 * @return bool Superadmin status
 */
function isSuperAdmin($conn) {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT role FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
        return $user && $user['role'] === 'Superadmin';
    } catch (Exception $e) {
        error_log("Error checking Superadmin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is Admin
 * 
 * @param PDO $conn Database connection
 * @return bool Admin status
 */
function isAdmin($conn) {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT role FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
        return $user && ($user['role'] === 'Admin' || $user['role'] === 'Superadmin');
    } catch (Exception $e) {
        error_log("Error checking Admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is Editor
 * 
 * @param PDO $conn Database connection
 * @return bool Editor status
 */
function isEditor($conn) {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT role FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
        return $user && ($user['role'] === 'Editor' || $user['role'] === 'Admin' || $user['role'] === 'Superadmin');
    } catch (Exception $e) {
        error_log("Error checking Editor status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get count of records in a table
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param string $condition Optional WHERE condition
 * @return int Record count
 */
function getCount($conn, $table, $condition = '') {
    $sql = "SELECT COUNT(*) FROM $table";
    if (!empty($condition)) {
        $sql .= " WHERE $condition";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

/**
 * Get recent articles
 * 
 * @param PDO $conn Database connection
 * @param int $limit Number of articles to return
 * @return array Recent articles
 */
function getRecentArticles($conn, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT a.*, c.name AS category_name, j.name AS journalist_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN journalists j ON a.journalist_id = j.id
        ORDER BY a.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get pending comments
 * 
 * @param PDO $conn Database connection
 * @param int $limit Number of comments to return
 * @return array Pending comments
 */
function getPendingComments($conn, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT c.*, a.title AS article_title
        FROM comments c
        JOIN articles a ON c.article_id = a.id
        WHERE c.status = 'pending'
        ORDER BY c.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get status badge class for article status
 * 
 * @param string $status Article status
 * @return string Bootstrap badge class
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'published':
            return 'success';
        case 'draft':
            return 'warning';
        case 'archived':
            return 'secondary';
        default:
            return 'info';
    }
}

/**
 * Upload file
 * 
 * @param array $file File data from $_FILES
 * @param string $folder Target folder
 * @param array $allowedTypes Allowed file types
 * @param int $maxSize Maximum file size in bytes
 * @return string|false File path on success, false on failure
 */
function uploadFile($file, $folder = 'uploads', $allowedTypes = null, $maxSize = 104857600) {
    // Vérifier si le fichier a été téléchargé sans erreurs
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Vérifier la taille du fichier
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Vérifier le type de fichier si des types sont spécifiés
    if ($allowedTypes !== null && !in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Créer le répertoire cible s'il n'existe pas
    $targetDir = '../' . $folder . '/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Générer un nom de fichier unique
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $fileExtension;
    $targetFile = $targetDir . $fileName;
    
    // Déplacer le fichier téléchargé vers le répertoire cible
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $folder . '/' . $fileName;
    }
    
    return false;
}

/**
 * Create slug from text
 * 
 * @param string $text Text to convert to slug
 * @return string Slug
 */
function createSlug($text) {
    // Remplacer les caractères non alphanumériques par -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    
    // Translittérer
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    
    // Supprimer les caractères indésirables
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    // Tronquer
    $text = trim($text, '-');
    
    // Supprimer les tirets en double
    $text = preg_replace('~-+~', '-', $text);
    
    // Mettre en minuscules
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Get all admin users
 * 
 * @param PDO $conn Database connection
 * @return array Admin users
 */
function getAdminUsers($conn) {
    $stmt = $conn->prepare("SELECT id, username, email, role, last_login FROM admin_users ORDER BY username");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get admin user by ID
 * 
 * @param PDO $conn Database connection
 * @param int $id Admin user ID
 * @return array|false Admin user data or false if not found
 */
function getAdminUserById($conn, $id) {
    $stmt = $conn->prepare("SELECT id, username, email, role, avatar, title, bio, notifications, email_notifications, last_login, created_at FROM admin_users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Update admin user last login time
 * 
 * @param PDO $conn Database connection
 * @param int $id Admin user ID
 * @return bool Success status
 */
function updateLastLogin($conn, $id) {
    $stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Log admin action
 * 
 * @param PDO $conn Database connection
 * @param int $adminId Admin user ID
 * @param string $action Action performed
 * @param string $details Action details
 * @return bool Success status
 */
function logAdminAction($conn, $adminId, $action, $details = '') {
    $stmt = $conn->prepare("
        INSERT INTO admin_logs (admin_id, action, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$adminId, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

/**
 * Create default admin user if none exists
 * 
 * @param PDO $conn Database connection
 * @return void
 */
function createDefaultAdminUser($conn) {
    // Vérifier si des utilisateurs admin existent
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count === 0) {
        // Créer l'utilisateur admin par défaut
        $stmt = $conn->prepare("
            INSERT INTO admin_users (username, password, email)
            VALUES (?, ?, ?)
        ");
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute(['admin', $password, 'admin@example.com']);
    }
}