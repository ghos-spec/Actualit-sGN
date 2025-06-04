<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once 'includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Mettre à jour les paramètres
        $settings = [
            'site_title' => sanitizeInput($_POST['site_title'] ?? ''),
            'site_description' => sanitizeInput($_POST['site_description'] ?? ''),
            'articles_per_page' => (int)($_POST['articles_per_page'] ?? 10),
            'enable_comments' => isset($_POST['enable_comments']) ? 1 : 0,
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("
                INSERT INTO settings (`key`, value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE value = ?
            ");
            $stmt->execute([$key, $value, $value]);
        }
        
        $success = 'Paramètres mis à jour avec succès.';
        
        // Journaliser l'action
        logAdminAction($conn, $_SESSION['admin_id'], 'update_settings', 'Mise à jour des paramètres du site');
        
    } catch (Exception $e) {
        $error = 'Une erreur est survenue : ' . $e->getMessage();
    }
}

// Récupérer les paramètres actuels
$site_title = getSetting($conn, 'site_title', 'Actualités Gabonaises');
$site_description = getSetting($conn, 'site_description', 'Votre source d\'actualités au Gabon');
$articles_per_page = (int)getSetting($conn, 'articles_per_page', '10');
$enable_comments = (bool)getSetting($conn, 'enable_comments', '1');
$maintenance_mode = (bool)getSetting($conn, 'maintenance_mode', '0');

include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Paramètres</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Paramètres</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= $success ?></div>
                            <?php endif; ?>
                            
                            <form action="settings.php" method="post">
                                <div class="form-group">
                                    <label for="site_title">Titre du site *</label>
                                    <input type="text" class="form-control" id="site_title" name="site_title" value="<?= htmlspecialchars($site_title) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="site_description">Description du site</label>
                                    <textarea class="form-control" id="site_description" name="site_description" rows="3"><?= htmlspecialchars($site_description) ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="articles_per_page">Articles par page</label>
                                    <input type="number" class="form-control" id="articles_per_page" name="articles_per_page" value="<?= $articles_per_page ?>" min="1" max="50">
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="enable_comments" name="enable_comments" <?= $enable_comments ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="enable_comments">Activer les commentaires</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" <?= $maintenance_mode ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="maintenance_mode">Mode maintenance</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Enregistrer les paramètres</button>
                                    <a href="index.php" class="btn btn-default">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 