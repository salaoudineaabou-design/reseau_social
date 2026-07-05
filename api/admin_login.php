<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$input = getJsonInput();
$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $input['password'] ?? '';

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['success' => false, 'message' => 'Identifiants incorrects.'], 401);
}

if (!in_array($user['role'], ['moderateur', 'admin'])) {
    jsonResponse(['success' => false, 'message' => 'Accès réservé au personnel administratif.'], 403);
}

$token = generateToken();
$stmt = $pdo->prepare('UPDATE users SET session_token = ? WHERE id = ?');
$stmt->execute([$token, $user['id']]);

jsonResponse([
    'success' => true,
    'session_token' => $token,
    'user' => publicUser($user),
]);
