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

// Rediriger vers l'interface Superadmin si l'utilisateur est Superadmin
if (isSuperAdmin($conn)) {
    header('Location: ../superadmin/users.php');
    exit;
} else {
    // Si l'utilisateur n'est pas Superadmin, afficher un message d'erreur
    $_SESSION['error'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page. Veuillez contacter un Superadmin.";
    header('Location: index.php');
    exit;
} 