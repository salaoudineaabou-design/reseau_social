<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$input = getJsonInput();

$current = $input['current_password'] ?? '';
$newPass = $input['new_password'] ?? '';

if (!password_verify($current, $user['password_hash'])) {
    jsonResponse(['success' => false, 'message' => 'Mot de passe actuel incorrect.'], 400);
}
if (strlen($newPass) < 6) {
    jsonResponse(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.'], 400);
}

$pdo = getDB();
$hash = password_hash($newPass, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$stmt->execute([$hash, $user['id']]);

jsonResponse(['success' => true, 'message' => 'Mot de passe modifié avec succès.']);
