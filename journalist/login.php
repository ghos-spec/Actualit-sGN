<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Vérifier si déjà connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['journalist_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Traiter le formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Vérifier les identifiants
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM journalists WHERE email = ? AND role = 'journaliste'");
        $stmt->execute([$email]);
        $journalist = $stmt->fetch();
        
        if ($journalist && $password === $journalist['password']) {
            // Définir les variables de session
            $_SESSION['journalist_id'] = $journalist['id'];
            $_SESSION['journalist_name'] = $journalist['name'];
            $_SESSION['journalist_email'] = $journalist['email'];
            $_SESSION['journalist_role'] = $journalist['role'];
            
            // Rediriger vers le tableau de bord
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Journaliste - Connexion</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Source Sans Pro', sans-serif;
        }
        .login-box {
            max-width: 400px;
            margin: 100px auto;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-logo a {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            text-decoration: none;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-body {
            padding: 30px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            padding: 8px 20px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-logo">
            <a href="../index.php">Actualités<span class="text-warning">GN</span></a>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h4 class="text-center mb-4">Espace Journaliste</h4>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Connexion</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="../index.php">Retour au site</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 