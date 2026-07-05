<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$input = getJsonInput();
$token = $input['token'] ?? '';
$password = $input['password'] ?? '';

if (!$token || strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => 'Requête invalide.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['success' => false, 'message' => 'Lien invalide ou expiré.'], 400);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?');
$stmt->execute([$hash, $user['id']]);

jsonResponse(['success' => true, 'message' => 'Mot de passe réinitialisé avec succès.']);
