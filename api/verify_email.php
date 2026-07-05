<?php
require_once __DIR__ . '/functions.php';

$token = $_GET['token'] ?? '';
if (!$token) jsonResponse(['success' => false, 'message' => 'Token manquant.'], 400);

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM users WHERE verify_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['success' => false, 'message' => 'Token invalide ou déjà utilisé.'], 404);
}

$stmt = $pdo->prepare('UPDATE users SET is_verified = 1, verify_token = NULL WHERE id = ?');
$stmt->execute([$user['id']]);

jsonResponse(['success' => true, 'message' => 'Email confirmé avec succès. Vous pouvez maintenant vous connecter.']);
