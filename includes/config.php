<?php
/**
 * Configuration file for the news website
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration du site
define('SITE_URL', 'http://localhost'); // Changer ceci par votre domaine en production
define('UPLOADS_DIR', 'uploads'); // Répertoire pour les fichiers téléchargés
define('ARTICLES_PER_PAGE', 10);
define('DATE_FORMAT', 'd F Y'); // Format pour l'affichage des dates
define('TIME_FORMAT', 'H:i'); // Format pour l'affichage des heures

// Configuration de la session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Rapport d'erreurs - mettre à 0 en production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration des logs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../debug.log');

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Définir l'encodage par défaut
mb_internal_encoding('UTF-8');

// Set default character set
header('Content-Type: text/html; charset=utf-8');