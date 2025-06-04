<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once 'includes/admin_functions.php';

// Créer l'utilisateur admin par défaut s'il n'existe pas
createDefaultAdminUser($conn);

// Vérifier si déjà connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Traiter le formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Vérifier les identifiants
        $stmt = $conn->prepare("SELECT id, username, password, email FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Mettre à jour la dernière connexion
            $stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Enregistrer l'historique de connexion
            $stmt = $conn->prepare("INSERT INTO login_history (user_id, login_time, ip_address, user_agent) VALUES (?, NOW(), ?, ?)");
            $stmt->execute([
                $user['id'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);

            // Définir les variables de session
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            
            // Journaliser l'action
            logAdminAction($conn, $user['id'], 'login', 'Connexion réussie');
            
            // Rediriger vers le tableau de bord
            header('Location: index.php');
            exit;
        } else {
            $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Connexion</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="../index.php">Actualités<span class="text-warning">GN</span></a>
        </div>
        
        <div class="card">
            <div class="login-card-body">
                <p class="login-box-msg">Connectez-vous pour accéder à l'administration</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form action="login.php" method="post">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Nom d'utilisateur" name="username" required>
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Mot de passe" name="password" required>
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Connexion</button>
                        </div>
                    </div>
                </form>
                
                <p class="mt-3 mb-1">
                    <a href="forgot_password.php">Mot de passe oublié ?</a>
                </p>
                <p class="mb-0">
                    <a href="../index.php" class="text-center">Retour au site</a>
                </p>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>