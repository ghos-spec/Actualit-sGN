<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../admin/includes/admin_functions.php'; // Adjusted path
require_once '../includes/db.php';

// Vérifier si l'utilisateur est connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn()) {
    header('Location: ../admin/login.php'); // Adjusted path
    exit;
}

// Vérifier si l'utilisateur est Superadmin, sinon rediriger
if (!isSuperAdmin($conn)) {
    $_SESSION['error'] = "Accès non autorisé à cette section.";
    header('Location: ../admin/index.php'); // Redirect to regular admin dashboard
    exit;
}

// Inclure le header Superadmin
include 'includes/superadmin_header.php';
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tableau de bord Superadmin</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Tableau de bord Superadmin</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Small boxes (Stat box) -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-info">
                        <div class="inner">
                            <?php
                            $stmt_users = $conn->query("SELECT COUNT(*) FROM admin_users");
                            $total_users = $stmt_users->fetchColumn();
                            ?>
                            <h3><?= $total_users ?></h3>
                            <p>Utilisateurs Admin</p>
                        </div>
                        <div class="icon">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <a href="users.php" class="small-box-footer">Plus d'infos <i class="bi bi-arrow-right-circle"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                 <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-success">
                        <div class="inner">
                             <?php
                            $stmt_articles = $conn->query("SELECT COUNT(*) FROM articles");
                            $total_articles = $stmt_articles->fetchColumn();
                            ?>
                            <h3><?= $total_articles ?></h3>

                            <p>Articles</p>
                        </div>
                        <div class="icon">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <a href="../admin/articles.php" class="small-box-footer">Plus d'infos <i class="bi bi-arrow-right-circle"></i></a> <!-- Link to admin articles -->
                    </div>
                </div>
                <!-- ./col -->
                 <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-warning">
                        <div class="inner">
                             <?php
                            $stmt_categories = $conn->query("SELECT COUNT(*) FROM categories");
                            $total_categories = $stmt_categories->fetchColumn();
                            ?>
                            <h3><?= $total_categories ?></h3>

                            <p>Catégories</p>
                        </div>
                        <div class="icon">
                            <i class="bi bi-folder"></i>
                        </div>
                         <a href="../admin/categories.php" class="small-box-footer">Plus d'infos <i class="bi bi-arrow-right-circle"></i></a> <!-- Link to admin categories -->
                    </div>
                </div>
                 <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-danger">
                        <div class="inner">
                             <?php
                            $stmt_journalists = $conn->query("SELECT COUNT(*) FROM journalists");
                            $total_journalists = $stmt_journalists->fetchColumn();
                            ?>
                            <h3><?= $total_journalists ?></h3>

                            <p>Journalistes</p>
                        </div>
                        <div class="icon">
                            <i class="bi bi-people"></i>
                        </div>
                         <a href="../admin/journalists.php" class="small-box-footer">Plus d'infos <i class="bi bi-arrow-right-circle"></i></a> <!-- Link to admin journalists -->
                    </div>
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
// Inclure le footer Superadmin
include 'includes/superadmin_footer.php';
?> 