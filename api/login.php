<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$input = getJsonInput();
$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $input['password'] ?? '';

if (!$email || !$password) {
    jsonResponse(['success' => false, 'message' => 'Email et mot de passe requis.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['success' => false, 'message' => 'Email ou mot de passe incorrect.'], 401);
}

if (!$user['is_verified']) {
    jsonResponse(['success' => false, 'message' => 'Veuillez confirmer votre email avant de vous connecter.'], 403);
}

if ($user['status'] !== 'actif') {
    jsonResponse(['success' => false, 'message' => 'Votre compte a été suspendu.'], 403);
}

$token = generateToken();
$stmt = $pdo->prepare('UPDATE users SET session_token = ? WHERE id = ?');
$stmt->execute([$token, $user['id']]);

jsonResponse([
    'success' => true,
    'message' => 'Connexion réussie.',
    'session_token' => $token,
    'user' => publicUser($user),
]);
