<?php
require_once __DIR__ . '/functions.php';
$user = requireAuth();
$input = getJsonInput();
$friendId = (int)($input['friend_id'] ?? 0);

if (!$friendId || $friendId === (int)$user['id']) {
    jsonResponse(['success' => false, 'message' => 'Requête invalide.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM friendships WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)');
$stmt->execute([$user['id'], $friendId, $friendId, $user['id']]);
if ($stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Une relation existe déjà avec cet utilisateur.'], 409);
}

$stmt = $pdo->prepare('INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, "pending")');
$stmt->execute([$user['id'], $friendId]);

jsonResponse(['success' => true, 'message' => 'Invitation envoyée.']);
