<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$pdo = getDB();
$stmt = $pdo->prepare('UPDATE users SET session_token = NULL WHERE id = ?');
$stmt->execute([$user['id']]);
jsonResponse(['success' => true, 'message' => 'Déconnexion réussie.']);
