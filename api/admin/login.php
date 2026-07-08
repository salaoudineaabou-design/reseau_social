<?php
/**
 * api/admin/login.php — POST
 * Connexion réservée au back-office : identique à login.php mais
 * refuse explicitement les comptes de rôle 'user'.
 * Reçoit : { email, mot_de_passe }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$body  = lire_json_body();
$email = trim($body['email'] ?? '');
$mdp   = (string)($body['mot_de_passe'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND email_verifie = 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
    exit;
}

// Vérification critique : un compte 'user' normal ne peut pas accéder au back-office
if ($user['role'] === 'user') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

if ((int)$user['is_banned'] === 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ce compte a été suspendu']);
    exit;
}

unset($user['mot_de_passe'], $user['token_email']);

echo json_encode(['success' => true, 'admin' => $user]);
