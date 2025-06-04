<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'admin/includes/admin_functions.php';

// Créer le dossier de test s'il n'existe pas
$test_dir = 'uploads/videos';
if (!file_exists($test_dir)) {
    mkdir($test_dir, 0777, true);
}

// Créer un fichier vidéo de test (1MB de zéros)
$test_file = $test_dir . '/test.mp4';
file_put_contents($test_file, str_repeat("\0", 1024 * 1024));

// Simuler un upload de fichier
$_FILES['test_video'] = [
    'name' => 'test.mp4',
    'type' => 'video/mp4',
    'tmp_name' => $test_file,
    'error' => 0,
    'size' => 1024 * 1024
];

echo "Détails du fichier de test :\n";
echo "Nom : " . $_FILES['test_video']['name'] . "\n";
echo "Type : " . $_FILES['test_video']['type'] . "\n";
echo "Taille : " . $_FILES['test_video']['size'] . " bytes\n";
echo "Chemin temporaire : " . $_FILES['test_video']['tmp_name'] . "\n";
echo "Erreur : " . $_FILES['test_video']['error'] . "\n";

// Vérifier si le fichier existe
if (file_exists($test_file)) {
    echo "Le fichier de test existe.\n";
} else {
    echo "Le fichier de test n'existe pas !\n";
}

// Tester l'upload
$result = uploadFile($_FILES['test_video'], 'uploads/videos', ['video/mp4'], 10485760);

if ($result) {
    echo "Test réussi ! Le fichier a été uploadé avec succès.\n";
    echo "Chemin du fichier : " . $result . "\n";
} else {
    echo "Test échoué ! L'upload a échoué.\n";
}

// Nettoyer
if (file_exists($test_file)) {
    unlink($test_file);
    echo "Fichier de test supprimé.\n";
}
?> 