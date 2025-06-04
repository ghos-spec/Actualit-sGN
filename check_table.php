<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $stmt = $conn->query("DESCRIBE articles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Structure de la table articles :\n";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 