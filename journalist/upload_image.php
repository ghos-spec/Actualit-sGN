<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Vérifier si connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['journalist_id']) || strtolower($_SESSION['journalist_role']) !== 'journaliste') {
    die(json_encode(['error' => 'Non autorisé']));
}

// Vérifier si une image a été uploadée
if (!isset($_FILES['file'])) {
    die(json_encode(['error' => 'Aucun fichier uploadé']));
}

$file = $_FILES['file'];
$upload_dir = '../uploads/articles/';

// Créer le dossier s'il n'existe pas
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Vérifier le type de fichier
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    die(json_encode(['error' => 'Type de fichier non autorisé']));
}

// Vérifier la taille du fichier (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    die(json_encode(['error' => 'Fichier trop volumineux']));
}

// Générer un nom de fichier unique
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$new_filename = uniqid() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// Déplacer le fichier
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Retourner l'URL de l'image
    $image_url = 'uploads/articles/' . $new_filename;
    die(json_encode(['location' => $image_url]));
} else {
    die(json_encode(['error' => 'Erreur lors de l\'upload']));
} 