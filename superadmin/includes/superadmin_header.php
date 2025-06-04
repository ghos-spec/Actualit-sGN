<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '../');
}

// Get admin user info
$adminUser = [];
if (isset($_SESSION['admin_id'])) {
    require_once '../admin/includes/admin_functions.php'; // Include admin functions
    require_once '../includes/db.php'; // Correction du chemin pour db.php
    $adminUser = getAdminUserById($conn, $_SESSION['admin_id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin - Actualités Gabonaises</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.css" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link href="../admin/assets/css/admin.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="bi bi-list"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="index.php" class="nav-link">Tableau de bord Superadmin</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="../../index.php" target="_blank" class="nav-link">Voir le site</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#">
                        <i class="bi bi-person-circle"></i>
                        <?= isset($adminUser['username']) ? htmlspecialchars($adminUser['username']) : 'Superadmin' ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="../admin/profile.php" class="dropdown-item">
                            <i class="bi bi-person-badge me-2"></i> Profil
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../admin/logout.php" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-2"></i> Déconnexion
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="index.php" class="brand-link">
                <span class="brand-text font-weight-light"><span class="text-warning">GN</span> Superadmin</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="index.php" class="nav-link">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <p>Tableau de bord Superadmin</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-file-text"></i>
                                <p>
                                    Articles
                                    <i class="bi bi-chevron-down right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../admin/articles.php" class="nav-link">
                                        <i class="bi bi-circle"></i>
                                        <p>Tous les articles</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../admin/add_article.php" class="nav-link">
                                        <i class="bi bi-circle"></i>
                                        <p>Ajouter un article</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-people"></i>
                                <p>
                                    Journalistes
                                    <i class="bi bi-chevron-down right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../admin/journalists.php" class="nav-link">
                                        <i class="bi bi-circle"></i>
                                        <p>Tous les journalistes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../admin/add_journalist.php" class="nav-link">
                                        <i class="bi bi-circle"></i>
                                        <p>Ajouter un journaliste</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-folder"></i>
                                <p>
                                    Catégories
                                    <i class="bi bi-chevron-down right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../admin/categories.php" class="nav-link">
                                        <i class="bi bi-circle"></i>
                                        <p>Toutes les catégories</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../admin/add_category.php" class="nav-link">
                                        <i class="bi bi-circle"></i>
                                        <p>Ajouter une catégorie</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/comments.php" class="nav-link">
                                <i class="nav-icon bi bi-chat-dots"></i>
                                <p>Commentaires</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/media.php" class="nav-link">
                                <i class="nav-icon bi bi-images"></i>
                                <p>Médiathèque</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link">
                                <i class="nav-icon bi bi-person-badge"></i>
                                <p>Utilisateurs Admin</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/settings.php" class="nav-link">
                                <i class="nav-icon bi bi-gear"></i>
                                <p>Paramètres</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/logout.php" class="nav-link">
                                <i class="nav-icon bi bi-box-arrow-right"></i>
                                <p>Déconnexion</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <?php
                    // Display session messages
                    if (isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['success']);
                    }
                    if (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['error']);
                    }
                    ?>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">