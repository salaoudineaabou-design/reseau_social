<?php
/**
 * api/config.example.php
 * -----------------------------------------------------------------
 * Modèle de configuration à copier vers api/config.php.
 * Ce fichier NE CONTIENT AUCUN vrai identifiant : remplis les valeurs
 * avec celles fournies par ton hébergeur (ou 'localhost' / 'root' /
 * '' en développement local avec XAMPP).
 *
 * IMPORTANT : api/config.php est ignoré par Git (voir .gitignore).
 * Ne commets jamais de vrais identifiants de base de données.
 * -----------------------------------------------------------------
 */

// ---- Paramètres de connexion MySQL --------------------------------
define('DB_HOST', 'localhost');        // ex: sql123.infinityfree.com en prod
define('DB_NAME', 'reseau_social');    // ex: if0_12345678_reseau en prod
define('DB_USER', 'root');             // ex: if0_12345678 en prod
define('DB_PASS', '');                 // mot de passe fourni par l'hébergeur
define('DB_CHARSET', 'utf8mb4');

// ---- Environnement -------------------------------------------------
// Mettre à false en production pour masquer les erreurs PHP
define('MODE_DEBUG', true);

// ---- Emails (Mailtrap) ----------------------------------------------
// InfinityFree et la plupart des hébergeurs gratuits bloquent mail().
// Créer un compte gratuit sur https://mailtrap.io et coller le token
// "Send API" ci-dessous. Laisser vide pour utiliser mail() en local.
define('MAILTRAP_TOKEN', '');
define('MAIL_FROM', 'noreply@monreseau.com');
define('MAIL_FROM_NAME', 'MonReseau Social');

// ---- URL de base du projet (utilisée pour construire les liens email) --
define('APP_BASE_URL', 'http://localhost/reseau_social/');

// =====================================================================
// NE PAS MODIFIER SOUS CETTE LIGNE — logique de connexion PDO
// =====================================================================

if (MODE_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données',
        'debug'   => MODE_DEBUG ? $e->getMessage() : null,
    ]);
    exit;
}

require_once __DIR__ . '/helpers.php';
