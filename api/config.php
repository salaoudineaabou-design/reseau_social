<?php
/**
 * config.php - Connexion à la base de données MySQL
 * Modifiez ces constantes selon votre environnement de déploiement.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'reseau_social');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_DIR_AVATARS', __DIR__ . '/../assets/images/avatars/');
define('UPLOAD_DIR_POSTS', __DIR__ . '/../assets/images/posts/');
define('UPLOAD_DIR_CHAT', __DIR__ . '/../assets/images/chat/');

// Création des dossiers d'upload s'ils n'existent pas
foreach ([UPLOAD_DIR_AVATARS, UPLOAD_DIR_POSTS, UPLOAD_DIR_CHAT] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données.']));
        }
    }
    return $pdo;
}
